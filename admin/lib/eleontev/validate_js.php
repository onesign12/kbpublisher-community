<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>Untitled</title>
</head>

<script>
<!--
function isBlank(val){
    if(val == null){ return true; }
    for(var i=0;i<val.length;i++) {
        if ((val.charAt(i)!=' ')&&(val.charAt(i)!="\t")&&(val.charAt(i)!="\n")&&(val.charAt(i)!="\r")){return false;}
    }
    return true;
}

function isValidEmailStrict(address) {
    if (isValidEmail(address) == false) return false;
    var domain = address.substring(address.indexOf('@') + 1);
    if (domain.indexOf('.') == -1) return false;
    if (domain.indexOf('.') == 0 || domain.indexOf('.') == domain.length - 1) return false;
    return true;
}


// Check that an email address is valid based on RFC 821 (?)
function isValidEmail(address) {
    if (address != '' && address.search) {
      if (address.search(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) != -1) return true;
      else return false;
    }
    
      // allow empty strings to return true - screen these with either a 'required' test or a 'length' test
       else return true;
}


function isInteger(val){
    if (isBlank(val)){return false;}
    for(var i=0;i<val.length;i++){
        if(!isDigit(val.charAt(i))){return false;}
    }
    return true;
}

function isNumeric(val) {
    return(parseFloat(val,10)==(val*1));
}

function isDigit(num) {
    if (num.length>1){return false;}
    var string="1234567890";
    if (string.indexOf(num)!=-1){return true;}
    return false;
}


function isBetween(value, min, max) {
    
    if(isInteger(value) || isNumeric(value)) { val = parseFloat(value); }
    else                                     { val = value.length; }
    
    if(max && max) { return (val >= min && val <= max); } 
    else if(min)   { return (val >= min); } 
    else if(max)   { return (val <= max); } 
    else           { return false; }
    return true;
}
 

//-->
</script>

<?php
require_once '_ValidatorJS.php';

$js = new ValidatorJscript();
//$js->required('321321', 'first_name');
//$js->required('15', array('first', 'two'));
//$js->email('email', 'email', 1);

//$js->between('between', 'email', 6, 10, 1);
//$js->compare('msg', 'email', 'first_name', '==');
$js->regex('regex', 'nonzero', 'first_name', 1);
echo $js->getScript();

//echo "<pre>"; print_r($js); echo "</pre>";
?>

<body>

<form onSubmit="return Validate(this)">

<input type="text" name="first_name" id="first_name">
<input type="text" name="email" id="email">
<input type="submit">
</form>

<script>
<!--
/*
re = new RegExp("^[a-zA-Z]+$");
r = re.test("asdsfer");
alert(r);
*/
//-->
</script>
</body>
</html>
