# KBPublisher Community Edition 

KBPublisher Comunity Edition is web based open source Knowledge Management Software.
Use it to share knowledge. Publish and manage articles, white papers, user manuals, business processes, FAQs, online help, APIs, or any other type of information.

The "KBPublisher Community Edition" is an open source application that includes basic functions for all users.

## Getting Started

These instructions will give you a copy of the project up and running on
your local machine.

### Requirements

Client Side:
- Operating System: Windows, Mac or Linux
- Web Browser: Internet Explorer 11+ or Chrome, Firefox, Safari.

Server side
- Operating System: Linux, Unix, Windows, Mac
- Web Server: Apache / Nginx / IIS 7.5 or above
- Database: MySQL 5.6.5 - 8.0
- Scripting Language: PHP 8.0 - 8.3

### Installing

In the example below, we assume you are installing KBPublisher in a kb folder in your web server's document root.  
The URL for your KB will look like this: http://domain.com/kb, you can use any directory you like.  
For example, if you want to install into the 'kbase' directory, change 'kb' to 'kbase'.  
Example: `git clone https://github.com/onesign12/kbpublisher-community.git kbase && cd kbase`  
To install to the root directory use './' instead of 'kb'.  

    cd /var/www # change to your web server document root
    git clone https://github.com/onesign12/kbpublisher-community.git kb && cd kb
    cp admin/.gitignore_prod admin/.gitignore
    cp setup/.gitignore_prod setup/.gitignore

Then open your browser, go to http://domain/kb/setup/index.php and follow the setup wizard instruction.

You can find documentation on our
[website](https://www.kbpublisher.com/kb/installing-kbpublisher_115.html)

### Documentation
- [Installation](https://www.kbpublisher.com/kb/installation-5/)
- [User Manual](https://www.kbpublisher.com/kb/user-manual-v80-1/)
- [Developer Manual](https://www.kbpublisher.com/kb/developer-manual-50/)
- [Knowledge base](https://www.kbpublisher.com/kb/)
