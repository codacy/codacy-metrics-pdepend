<?php
// #Metrics: {"complexity": 1, "loc": 33, "cloc": 5, "nrMethods": 4, "nrClasses": 2}

// #LineComplexity: 1
function antiSpam($email)
{
	$remove_spam = "![-_]?(NO|I[-_]?HATE|DELETE|REMOVE)[-_]?(THIS)?(ME|SPAM)?[-_]?!i";
	return preg_replace($remove_spam, "", trim($email));
}

class Foo
{
	public $aMemberVar = 'aMemberVar Member Variable';
	public $aFuncName = 'aMemberFunc';


	// #LineComplexity: 1
	function fooster()
	{
		print 'Inside `aMemberFunc()`';
	}
}

namespace MyBarPackage\MyBarInnerPackage {
	class Bar
	{
		public $aMemberVar = 'aMemberVar Member Variable';
		public $aFuncName = 'aMemberFunc';


		// #LineComplexity: 1
		function barista()
		{
			print 'Inside `aMemberFunc()`';
		}
	}
}

namespace MyFunctions {
	// #LineComplexity: 1
	function clean($email)
	{
		$remove_spam = "![-_]?(NO|I[-_]?HATE|DELETE|REMOVE)[-_]?(THIS)?(ME|SPAM)?[-_]?!i";
		return preg_replace($remove_spam, "", trim($email));
	}
}
