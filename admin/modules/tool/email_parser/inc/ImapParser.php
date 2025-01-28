<?php
//In this version we consider only "legal" messages, which means the folowing:
//1. each attachment is enclosed in one and the single body part
//2. such bodyparts have the field "disposition" set to "ATTACHMENT"
//3. text content of a message is always contained in first bodypart (subtype "PLAIN", this is mandatory), or spred
//over two bodyparts (the second is of subtype "HTML"), or spred over two subparts of 1st bodypart (subtype "MULTIPART"). 
//As was agreed earlier, only subtype "TEXT/PLAIN" is of interest.
//Other combinations of parttype/subtype are ignored in current version

//The fact is: message requisits, i.e., "From","To", "Subject", "Date" etc. from one hande, and message content, 
//i.e. message text and attachment(s) from the other hand are retrieved thru the use of quite different library functions, 
//and morover, they are stored in differnet tables, and attachments in turn are stored in different table records, 
//This results in  different SQL stmts, one per each bodypart (text&attachment(s) ).
//So we should have some kind of "external synchronization" for not to mess messges, their attributes and bodies&attachments. 
//This is achieved by external (with respect to this object) sequential calls of the various functions
//Use of a single method call (aka "wrapper") for message body&requisits  "at-once-retrieval" 
//immediately leads to excessive memory usage.

class ImapParser {

	private $mbox = null;
	private $imap_conn_str;
	private $imap_user;
	private $imap_psw;
	
	//For this application only unseen messages are processed.
	//priv decl  "$process_only_unseen = true" is reserved for possible more sophisticated 
	//message processing in future
	//So,following MS's manner, we write: "This behaviour is by design" :-)	
	//More exactly, this is done in order to not duplicate info in databse, 
	//We only add info extracted from yet unprocessed msgs
	private $process_only_unseen = true;

    //For message flow history to be kept not only in databse but at mail server as well, 
    //in this version we do not delete processed messages
	private $delete_msg_on_read = false;

	private $error_str = '';
    
    private $attachment_table = array();
	
	
	function __construct($setting, $process_only_unseen = true, $delete_msg_on_read = false) {
		
		$this->imap_user = $setting['imap_user'];
		$this->imap_pass = $setting['imap_pass'];
        
		if(empty($setting['imap_conn_str'])) {
			$str = '{%s:%s/imap%s}%s';
	        $flags = (empty($setting['ssl'])) ? '' : '/ssl';
			$flags .= (empty($setting['ssl'])) ? '' : '/novalidate-cert';
	        $mailbox_str = sprintf($str, $setting['host'], $setting['port'], $flags, $setting['mailbox']);
			$this->imap_conn_str = $mailbox_str;
		} else {
			$this->imap_conn_str = $setting['imap_conn_str'];
		}
		
		$this->process_only_unseen = $process_only_unseen;
		$this->delete_msg_on_read = $delete_msg_on_read;
	}


	function __destruct () {
		if ( isset($this->mbox) ) {
			imap_close( $this->mbox );
		}
	}
		
		
	function getLastError() { 
		return $this->error_str; 
	}
	
		
	private function isStreamValid() {

		if ( !imap_ping( $this->mbox ) ) {
			if ( !$this->Open() ) return false;
		}
		$this->error_str = '';
		return true;	
	}
    
    
    public function open() {
		$this->mbox = @imap_open("{$this->imap_conn_str}", $this->imap_user, $this->imap_pass);
        imap_errors();
        imap_alerts();
        
        if (!(boolean) $this->mbox) {
		    $this->mbox = null;
            $this->error_str = "Error (re)opening mbox stream: " . imap_last_error();
            return false;
        }
        
        $this->error_str = "";
        return true;
    }
	
	
	function getUnseenMsgNums() {
		$unseenMsgsNums = array();
		if (!$this->isStreamValid()) {
			return false;
		}

		$MC = imap_check($this->mbox);
		if ($MC->Nmsgs == 0) {
			$this->error_str = 'Mailbox empty';
			return false;
		}
		
    	$tmparr = imap_fetch_overview($this->mbox, "1:{$MC->Nmsgs}", 0);
		
		if ($tmparr === false) {
			$this->error_str = 'Error retrieving mbox overview 1:{$MC->Nmsgs} : ' . imap_last_error();
			return false;
		}

		foreach ($tmparr as $val) {
			if ($this->process_only_unseen) {
				if (!(boolean)($val->seen)) {
					$unseenMsgsNums[] = $val->msgno; 
				}	
			} else {
				$unseenMsgsNums[] = $val->msgno;
			}
		}
		
		if (empty($unseenMsgsNums) && $this->process_only_unseen) {
			$this->error_str = 'No unseen messages found';
			return false;
		}
		
		$this->error_str = '';
		return $unseenMsgsNums;
	}
    
    
    function getMsgUid($msgno) {
        return imap_uid($this->mbox, $msgno);
    }


	function setMsgProcessed($uid) {
		if (!$this->isStreamValid()) {
			return false;	
		}

        if ($this->delete_msg_on_read) {
            imap_delete($this->mbox, $uid, FT_UID);
            //imap_setflag_full($this->mbox, $uid, "\\Deleted", ST_UID); // the same
            return true;
        }
         	
		if (($res = imap_setflag_full($this->mbox, $uid, "\\Seen", ST_UID))) { // doesn't work for uid, msg number only
			$res = $uid;
			$this->error_str = '';
		} else {
			$res = false;
			$this->error_str = "Error setting message {$uid} processed " . imap_lastr_error();
		}
		
		return $res;
	}
    
    
    function deleteProcessedMsgs() {
         imap_expunge($this->mbox);
    }	


	protected function decodeBodyPart(& $encodedBodyPart, $encoding ) {
	
		if ( !strlen($encodedBodyPart )) {
			return false;
		}
			
		switch ($encoding) {
			case 0: //7-bit 
				$enc = mb_detect_encoding($encodedBodyPart,"ASCII,ISO-8859-1,UTF7",true);
				switch ($enc) {
					case 'ASCII':
					case 'ISO-8859-1':
						$res = $encodedBodyPart;
					break;
					
					case 'UTF7':
						$res = @imap_utf7_decode($encodedBodyPart) ;
/*						{
					if (PHP_VERSION >= 5) {
						$res = @convert_uudecode( $encodedBodyPart );
					}	
*/					
					break;
					default:
					break;
				}

			break;		
			case 1: //8-bit. 
				$res = imap_qprint ( imap_8bit ( $encodedBodyPart ) );					 
			break;
			case 2: //binary. Aint seen yet
				$res = false;
			break;	
			case 3: //base64
				$res = imap_base64 ( $encodedBodyPart );
			break;			
			case 4: //quoted printable
				$res = imap_qprint( $encodedBodyPart );
			break;
			case 5: //Other
				$res = false;
			break;	
			default:
			break;	
		}
		
		return $res;
	}
    
    
    function getHeader($msgno) {
        if (!$this->isStreamValid()) {
            return false;
        }
        
        return imap_fetchheader($this->mbox, $msgno);
	}
    
    
    function getBody($msgno) {
        if (!$this->isStreamValid()) {
            return false;
        }
        
        return imap_body($this->mbox, $msgno);
    }


	function getMsgRequisits($msgno) {
		if (!$this->isStreamValid()) {
			return false;
		}
        
		$res = array(); 
		$tmparr = imap_fetch_overview($this->mbox, $msgno);
        $tmparr2 = imap_header($this->mbox, $msgno);
		
		if ( !(boolean)$tmparr ) {
			$this->error_str = "Error retrieving mbox overview for msgno={$msgno}: " . 
			imap_last_error();
			return false;			
		}
        
        // from
        $res['from'] = $tmparr2->from[0]->mailbox . '@' . $tmparr2->from[0]->host;
        
        // to
        $res['to'] = $tmparr2->to[0]->mailbox . '@' . $tmparr2->to[0]->host;
        
        $res['name'] = $tmparr2->from[0]->personal;
        
		/*if (isset($tmparr[0]->from)) { // 2nd way to retrieve headers
			$t1 = imap_mime_header_decode($tmparr[0]->from);
            $res['from'] = $t1[0]->text; // it can be just a name
            
		} else {
			$res['from'] = '';		
        }*/
       
        // cc
        if (!empty($tmparr2->cc)) {
            foreach ($tmparr2->cc as $v) {
                $cc = array('email' => $v->mailbox . '@' . $v->host);
                if (!empty($v->personal)) {
                    $cc['name'] = $v->personal;
                }
                
                $res['cc'][] = $cc;
            }
        }
        
		if ( !empty ( $tmparr[0]->subject )) {
			// convert message to utf8
			$subject = imap_utf8($tmparr[0]->subject);
			$t1 = imap_mime_header_decode($subject);
			$res['subject'] = $t1[0]->text;
		} else {
			$res['subject'] = '';
		}	

		$t1 = imap_mime_header_decode($tmparr[0]->date);	
		$res['date'] = $t1[0]->text;

		$t1 = imap_mime_header_decode($tmparr[0]->message_id);	
		$res['message_id'] = $t1[0]->text;	

		if ( isset ( $tmparr[0]->references ) ) {
			$t1 = imap_mime_header_decode($tmparr[0]->references);
			$res['references'] = $t1[0]->text;			
		} else {
			$res['references'] = "";
		}
			
		$res['size'] = $tmparr[0]->size;
        $res['message_id'] = $tmparr2->message_id;
        $res['in_reply_to'] = $tmparr2->in_reply_to;

		$this->error_str = '';			
		return $res;
	}
	
	
	function getMsgBodyPartsProps( $msgno ) {
		$props = array(); 

		if ( !$this->isStreamValid() ) {
			return false;		
		}	
		
		$msgstruct = imap_fetchstructure($this->mbox,$msgno,0);
		
		if ( !(boolean)$msgstruct ) {	
			$this->error_str = "Error retrieving msg structure for msgno={$msgno}: " . imap_last_error();
		return false;
		}
		
		if ( !isset( $msgstruct->parts ) ) {
			$this->error_str = 'No parts found in message {$msgno}';
			return false;
		}

			
		foreach ($msgstruct->parts as $partno=>$partval) {
			if ( 2 == $partval->type) continue;
			//2 corresponds to "MESSAGE" This is in most cases means disposition="DISPOSITION-NOTIFICATION", 
			//eg, Read Notification. Ignored in current version.
			if ( 0 == $partval->type ) { //primary body type 0 corresponds to TEXT
				if ( $partval->subtype == "PLAIN" ) {
					//One more possible subtype is "HTML". As was agreed earlier, subtype of this kind may be ignored,
					//but only in case when the part of subtype "PLAIN" exists. Other subtypes currently ignored.
					//Similarly, for message body part of type 1 ("MULTIPART")  there can be subparts of subtypes
					// "PLAIN" and "HTML" 
					//(the latest is ignored, but this case requires a bit more subtle processing. See "MULTIPART" subsection below), 
					//Any single is contained in a single body part
					//TODO: Text body may be encoded as koi8-r or 	else. "parameters" array must be checked			
					$props[] = array('partindex'=>$partno+1,
								'part_type'=>"TEXT",
								'filename'=>"",
								'transferencoding'=>$partval->encoding);
				} else {
						if ( $partval->subtype == "HTML" ) {
							$props[] = array('partindex'=>$partno+1,
										'part_type'=>"HTML",
										'filename'=>"",
										'transferencoding'=>$partval->encoding);						
						}
					}
						
			} elseif ( 1 == $partval->type ) { //1 means "MULTIPART"
				
					if ( $partval->subtype == "ALTERNATIVE" ) { 
	
						//"ALTERNATIVE" means presence of subbarts of different subtypes
						//Usually, the first subpart is of subtype "PLAIN". Other subparts are ignored in current version
						//TODO: "foreach" thru subparts of a given message part of type "ALTERNATIVE"
						//TODO: and excplicitly retrieve  subparts	 partindexes
						$props[] = array('partindex'=>strval($partno+1) . "1",
										'part_type'=>"TEXT",
										'filename'=>"",
										'transferencoding'=>$partval->encoding);																	
					}
				
				}	else { //Other primary body types correspond to inserted and/or attached entities.
							//The former are ignored, so we check disposition field if it is "ATTACHEMENT"
						if ( $partval->ifdisposition ) {
							if ( $partval->disposition == "ATTACHMENT" ) {	
							//Other types of disposition are ignored. See above.	
								$att_name_hdr = array();
								//Retrieving attached file name
								$att_name_hdr = imap_mime_header_decode($partval->dparameters[0]->value);
								$props[] = array('partindex'=>$partno+1,'parttype'=>"ATTACHMENT",
										'filename'=>$att_name_hdr->value,
										'transferencoding'=>$partval->encoding);							
	                        }
						}
					}
			}
				
		$this->error_str = "";			
		return $props;
	}
	
	
	function getMsgText($msgno) {
	    
		if (!$this->isStreamValid()) {;
			return false;
        }
		
		// obtain message's charset from the headers
		$headers = imap_fetchmime($this->mbox, $msgno, '1.1');
		preg_match('/charset=(.+)/', $headers, $matches);
		$charset = (isset($matches[1])) ? trim($matches[1]) : false;
		
		$text = '';
        $is_html = false;

		$textstruct = imap_bodystruct($this->mbox, $msgno, 1);
		if (!$textstruct) {
			$this->error_str = "Error retrieving body structure for msgno={$msgno} partindex=1: " . imap_last_error();
			return false;
		}
        
		if ($textstruct->type == 0) {
			if (($textstruct->subtype == 'PLAIN') || ($textstruct->subtype == 'HTML')) {
				$encodedText = imap_fetchbody($this->mbox, $msgno, '1', FT_PEEK);
                
                $text = $this->decodeBodyPart($encodedText, $textstruct->encoding);
                			
				if (!$text) {
					$this->error_str = "Decode error. MsgNo: {$msgno} Encoding: {$textstruct->encoding}";			
					return false;				
				}
                
                $is_html = ($textstruct->subtype == 'HTML');
			}
            
		} elseif ($textstruct->type == 1) {
		    if ($textstruct->subtype == 'ALTERNATIVE') {
				$textsubstruct = imap_bodystruct($this->mbox, $msgno, '1.1');
				$encodedText = imap_fetchbody($this->mbox, $msgno, '1.1', FT_PEEK);
                
                $text = $this->decodeBodyPart($encodedText, $textsubstruct->encoding);
				if (!$text) {
				    $this->error_str = "Decode error. MsgNo: {$msgno} Encoding: {$textstruct->encoding}";
                    return false;
                }												
			}
        }
        
		$this->error_str = '';
        
		// convert the message to utf8
		if ($charset && strtolower($charset) != 'utf-8') {
			$text = mb_convert_encoding($text, 'UTF-8', $charset);
		}
		
		return array($text, $is_html);
	}
	
	
	function getAttachmentTable($msgno) {
		$res = array();
		if (!$this->isStreamValid()) {
		    return false;
		}
	
		$msgstruct = imap_fetchstructure($this->mbox, $msgno);
		
		if (!(boolean)$msgstruct) {
			//imap_fetchstructure returns FALSE (not null) on failure	
			//Again, this is the figure of style :-)				
			$this->error_str = "Error retrieving msg structure for msgno={$msgno}: " . imap_last_error();
			return false;
		}
		
		//Recently I caught message containing tex but with no pats (!!!!).So, this check is not thoroghly sparse	
		//TODO: Clear up what fuck the message it was
		if (!isset($msgstruct->parts)) { // it's so-called "simply email", maybe it isn't an error #panychek
			$this->error_str = "Message with no parts";
			return false;
		}
        
        $this->attachment_table = array();
        $this->parseMessageParts($msgstruct->parts);
        
		$this->error_str = '';
		return $this->attachment_table;	
	}
    
    
    function parseMessageParts($parts, $parent_section = '') {
        
        foreach($parts as $subsection => $part){
            $section = $parent_section . ($subsection + 1);
            
            if(isset($part->parts)) { // extra dimensions
                $this->parseMessageParts($part->parts, $section . '.');
    
            } else {
                
                if ($part->ifdisposition == 1) {
                    if(in_array(strtoupper($part->disposition), array('ATTACHMENT', 'INLINE'))) {
                        
                        $att_name_hdr = imap_mime_header_decode($part->dparameters[0]->value);
					    $res = array('partindex' => $section,
								   'filename' => $att_name_hdr[0]->text,
								   'transfer_encoding' => $part->encoding);
                        $this->attachment_table[] = $res;
                    }
                }
            }
        }
    }
    
	
	
	function getMsgBodyPart	($msgno, $partindex, $tr_enc,$callback = null) {
		//Callback parameter is reserved for future implementation improvements	
		$decodedBodyPart = '';
		if ( !$this->isStreamValid() ) return false;
		if ( false === ($decodedBodyPart = $this->decodeBodyPart(
										imap_fetchbody($this->mbox,$msgno,
										$partindex,
										FT_PEEK),
						$tr_enc )) ) {
			$this->error_str = "Decode error. MsgNo: {$msgno} Encoding: {$tr_enc}";			
			return false;
		}
		if (is_callable( $callback )) {
			call_user_func( $callback );
		}
		$this->error_str = "";
		return $decodedBodyPart;
	}
	
	
}//class
?>