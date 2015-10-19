<?php

/**
*
*
* This is a stand-alone program to display class methods found
* within a Target Directory defined in the $project directory var below.
* Change this var to relect the path to he class files that you wish to
* display.  Then point your browser to this file (http://yourDomain.ext/thisfile)
*/

// Set the target directory here
$project_dir = '/var/web-hosts/phpFox38/public_html/include/library/phpfox/twitter';

$ds = array($project_dir);

// Initialize if looking at Phpfox classes
// If you are not targeting Phpfox classes, comment out these lines
define('PHPFOX', true);
define('PHPFOX_DS', DIRECTORY_SEPARATOR);
define('PHPFOX_DIR', dirname(__FILE__) . PHPFOX_DS);
define('PHPFOX_START_TIME', array_sum(explode(' ', microtime())));
require(PHPFOX_DIR . 'include' . PHPFOX_DS . 'init.inc.php');
//End Initialize

function joinpaths() {
    $aArgs=func_get_args();
	return $aArgs[0].'/'.$aArgs[1];
}

//Build recursive directory list of files and include them
//NOTE: This can lead to problems if class extension precedes the extended class 
while(!empty($ds)) {
    $dir = array_pop($ds);
    if(($dh=opendir($dir))!==false) {
        while(($file=readdir($dh))!==false) {
            if($file[0]==='.') continue;
            $path = joinpaths($dir,$file);
            if(is_dir($path)) {
                $ds[] = $path;
            } else {
                try{
                    include_once $path;
                } catch(Exception $e) {
                    echo 'EXCEPTION: '.$e->getMessage().PHP_EOL;
                }
            }
        }
    } else {
        echo "ERROR: Could not open directory '$dir'\n";
    }
}

foreach(get_declared_classes() as $c) { // get a class from array of declared classes
    $class = new ReflectionClass($c); // Define a reflection class object of this class
    $mc=$class->getDocComment(); // Extract the Class Comment from the object
    if($mc !== false) {
        $mcc=explode("\n", $mc); // Array comment into lines
        $mccc=implode('<br />', $mcc); // Convert back to string with html breaks
        echo '<br />'.$mccc.''; // Display document comment
    }
    $methods = $class->getMethods(); // Get all of the methods in a class
    foreach($methods as $m) {
        $pm=$m->getParameters(); // Get an array of parameters for the method
        $dc = $m->getDocComment(); //Get Method comment
        $nm=$class->getName().'::'.$m->getName(); // Get the full name of the method (Class::Method)

        // Build the method comment and display
        if($dc !== false) {
            $dcc=explode("\n", $dc);
            $dccc=implode('<br />', $dcc);
            echo '<br /><br />';
            echo $dccc.PHP_EOL;
        }

        echo '<br /><b>'; //Put line after comment
        // Display Method name
        echo $nm;

        $iEnd=count($pm);
        $iCount=0;

        // Display paramters after function name
        echo '(';
        foreach($pm as $key=>$pmItem) {
           $iCount++;
           echo '$'.$pmItem->name;
           echo ($iCount==$iEnd)?'':', ';
        }
        echo ')</b>';
    }
    echo '<br />';
}

?>
