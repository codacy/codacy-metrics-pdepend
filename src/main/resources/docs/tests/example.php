// #Metrics: {"complexity": 1}
<?php

// $Id$

// Try to remove anti-SPAM bits
function antiSpam($email)
{
    $remove_spam = "![-_]?(NO|I[-_]?HATE|DELETE|REMOVE)[-_]?(THIS)?(ME|SPAM)?[-_]?!i";
    return preg_replace($remove_spam, "", trim($email));
}

class Foo {
    public $aMemberVar = 'aMemberVar Member Variable';
    public $aFuncName = 'aMemberFunc';


    function fooster() {
        print 'Inside `aMemberFunc()`';
    }
}

namespace MyBarPackage\MyBarInnerPackage {
	class Bar {
	    public $aMemberVar = 'aMemberVar Member Variable';
	    public $aFuncName = 'aMemberFunc';


	    function barista() {
	        print 'Inside `aMemberFunc()`';
	    }
	}
}

namespace MyFunctions{
	// Try to remove anti-SPAM bits
	function clean($email) {
	    $remove_spam = "![-_]?(NO|I[-_]?HATE|DELETE|REMOVE)[-_]?(THIS)?(ME|SPAM)?[-_]?!i";
	    return preg_replace($remove_spam, "", trim($email));
	}
}

?>
