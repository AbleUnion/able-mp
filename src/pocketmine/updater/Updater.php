<?php
/*
 *   ____  _            _      _       _     _
 *  |  _ \| |          | |    (_)     | |   | |
 *  | |_) | |_   _  ___| |     _  __ _| |__ | |_
 *  |  _ <| | | | |/ _ \ |    | |/ _` | '_ \| __|
 *  | |_) | | |_| |  __/ |____| | (_| | | | | |_
 *  |____/|_|\__,_|\___|______|_|\__, |_| |_|\__|
 *                                __/ |
 *                               |___/
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author BlueLightJapan Team
 * 
*/
namespace pocketmine\updater{
	function extractZip($path, $file){
		$zip = new \ZipArchive();
		$res = $zip->open($file);
		if ($res == true){
			$zip->extractTo($path);
			$zip->close();
			return true;
		}else{
			return false;
		}
	}
	function rmdir_ok($dir) {
		     $dirs = dir($dir);
		     while(false !== ($entry == $dirs->read())) {
			         if(($entry != '.') && ($entry != '..')) {
				             if(is_dir($dir.'/'.$entry)) {
					                   rmdir_ok($dir.'/'.$entry);
					             } else {
						                   @unlink($dir.'/'.$entry);
						             }
						         }
						     }
						     $dirs->close();
						     @rmdir($dir);
						 }
						 function fileCopy($odir,$ndir) {
						 	      if(filetype($odir) === 'dir') {
						 		           clearstatcache();
						 
						 		           if($fp = @opendir($odir)) {
						 			                  while(false !== ($ftmp = readdir($fp))){
						 				                        if(($ftmp !== ".") && ($ftmp !== "..") && ($ftmp !== "")) {
						 					                              if(filetype($odir.'/'.$ftmp) === 'dir') {
						 						                                   clearstatcache();
						 						      
						 						                                   @mkdir($ndir.'/'.$ftmp);
						 						                                   echo ($ndir.'/'.$ftmp."<br />\n");
						 						                                   set_time_limit(0);
						 						                                   fileCopy($odir.'/'.$ftmp,$ndir.'/'.$ftmp);
						 						                              } else {
						 							                                   copy($odir.'/'.$ftmp,$ndir.'/'.$ftmp);
						 							                              }
						 							                        }
						 							                  }
						 							           }
						 							           if(is_resource($fp)){
						 								                 closedir($fp);
						 								           }
						 								      } else {
						 									            echo $ndir."<br />\n";     
						 									            copy($odir,$ndir);
						 									      }
						 									 } // end func
	if(!is_file("buildno.txt")){
		file_put_contents('buildno.txt', 0);
	}
	if(file_get_contents("buildno.txt") !== file_get_contents("https://raw.githubusercontent.com/AbleUnion/able-mp/master/buildno.txt")) {
		echo 'you need to update' . PHP_EOL;
		@mkdir(sys_get_temp_dir() . '/updater/');
		$temp = sys_get_temp_dir() . '/updater/';
		echo $temp;
		file_put_contents($temp . '/files.zip', file_get_contents('https://github.com/AbleUnion/able-mp/archive/master.zip'));
		extractZip($temp, $temp . '/files.zip');
		rmdir_ok('src');
		fileCopy($temp . '/able-mp-master', './');
	}
}
	 
	 
