<?php

/**
 * FileManager plugin
 *
 *   Copyright 2016 Survingo
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

namespace Survingo\FileManager;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class FileManager extends PluginBase{
	
	/** @var string $prefix */
	protected $prefix = "§7[§fFileManager§7]§f ";
	
	public function onEnable(){
		$this->getServer()->getLogger()->info("§aEnabling §2" . $this->getDescription()->getFullName() . "§a by Survingo...");
		if(!is_dir($this->getDataFolder())) @mkdir($this->getDataFolder());
		if(!is_dir($this->getDataFolder() . "gz")) @mkdir($this->getDataFolder() . "gz");
		if(!is_dir($this->getDataFolder() . "phar")) @mkdir($this->getDataFolder() . "phar");
		if(!is_dir($this->getDataFolder() . "zip")) @mkdir($this->getDataFolder() . "zip");
		$this->getLogger()->info("§aSuccessfully enabled!");
		$this->getLogger()->info("§bThis plugin is still in development. Please report bugs and issues on the GitHub page\n§1http://github.com/Survingo/FileManager-plugin/issues");
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch(strtolower($command->getName())){
			case "file":
				if(count($args) < 1){
					$messages = [
					"----------",
					"List of available commands",
					"§7/§6file §egz§f <§7create§f|§7extract§f> <§7filename§f>",
					"§7/§6file §ephar§f <§7create§f|§7unphar§f> <§7filename§f>",
					"§7/§6file§c rar",
					"§7/§6file §ezip§f <§7create§f|§7unzip§f> <§7filename§f>",
					"----------"
					];
					foreach($messages as $help){
						$sender->sendMessage($help);
					}
					return true;
				}
			$arg = array_shift($args);
			switch($arg){
			case "phar":
				if(!extension_loaded("phar")){
					$sender->sendMessage($this->prefix . "§cPHAR extension is not loaded!");//If you get this error I wonder how you enabled this plugin
					return true;
				}
				if(count($args) < 2 || count($args) === 0){
					$sender->sendMessage("Not enough arguments. Usage: §7/§6file §ephar§f <§7create§f|§7unphar§f> <§7filename§f>");
					return true;
				}
				$arg = array_shift($args);
				switch($arg){
					case "extract":
					case "unphar":
						try{
							$phar = new \Phar($this->getDataFolder() . "phar/" . $args[0] . ".phar");
							if(is_dir($this->getDataFolder() . "phar/" . $args[0])){
								$sender->sendMessage($this->prefix . "§7" . realpath($this->getDataFolder() . "phar/" . $args[0]) . "§f already exists, please delete it firstly.");
								return true;
							}
							$sender->sendMessage("§7Extracting PHAR now, could take some seconds...");
							$phar->extractTo($this->getDataFolder() . "phar/" . $args[0]);
							$sender->sendMessage("§aPHAR has been successfully extracted to §2" . realpath($this->getDataFolder() . "phar/" . $args[0]));
						}catch(\Exception $e){
							$sender->sendMessage($this->prefix . "§cException found: " . $e->getMessage());
						}
						return true;
					break;
					case "create":
					case "make":
						$file = $this->getDataFolder() . "phar/" . $args[0];
						if(!is_dir($file)){
							$sender->sendMessage($this->prefix . "§cCan't phar §4" . $this->getDataFolder() . "phar/" . $args[0] . "§c because it doesn't exists!");
							return true;
						}
						if(file_exists($file . ".phar")){
							$sender->sendMessage("§7PHAR already exists, overwriting...");
							unlink($file . ".phar");
						}
						$pathInfo = pathInfo($file);
						$parentPath = $pathInfo['dirname'];
						$dirName = $pathInfo['basename'];
						$phar = new \Phar($file . ".phar");
						$phar->startBuffering();
						$phar->setSignatureAlgorithm(\Phar::SHA1);
						$this->rephar($sender, $file, strlen("$parentPath/"), $phar);
						$phar->stopBuffering();
						$sender->sendMessage("§aPHAR has been successfully created on §2" . realpath("$file.phar"));
						return true;
					break;
					default:
						$sender->sendMessage("§7/file phar $args[0]§f doesn't exists!\nPlease use §7/§6file §ephar§f <§7create§f|§7unphar§f> <§7filename§f>");
						return true;
					break;
				}
			break;
			case "rar":
				if(extension_loaded("rar")){
					$sender->sendMessage("§bRAR files aren't supported yet. Please try again in further versions of this plugin.");
					return true;
				}else{
					$sender->sendMessage($this->prefix . "§cRAR extension is not loaded!");
					return true;
				}
			break;
			case "zip":
				if(!extension_loaded("zip")){
					$sender->sendMessage($this->prefix . "§cZIP extension is not loaded!");
					return true;
				}
				if(count($args) < 2 || count($args) === 0){
					$sender->sendMessage("Not enough arguments. Usage: §7/§6file §ezip§f <§7create§f|§7unzip§f> <§7filename§f>");
					return true;
				}
				$arg = array_shift($args);
				switch($arg){
					case "extract":
					case "unzip":
						$file = realpath($this->getDataFolder() . "zip/" . $args[0] . ".zip");
						if(!file_exists($file)){
							$sender->sendMessage($this->prefix . "§4" . $this->getDataFolder() . "zip/" . $args[0] . ".zip§c doesn't exists!");
							return true;
						}
						if(is_dir($this->getDataFolder() . "zip/" . $args[0])){
							$sender->sendMessage($this->prefix . "§7" . realpath($this->getDatafolder() . "zip/" . $args[0]) . "§f already exists, please delete it firstly.");
							return true;
						}
						$zip = new \ZipArchive();
						$zip->open($file);
						$zip->extractTo($this->getDataFolder() . "zip/" . $args[0]);
						$zip->close();
						$sender->sendMessage($this->prefix . "§aZIP has been successfully extracted to §2" . realpath($this->getDataFolder() . "zip/" . $args[0]));
						return true;
					break;
					case "create":
					case "make":
						$file = $this->getDataFolder() . "zip/" . $args[0];
						if(!is_dir($file)){
							$sender->sendMessage("§cCan't zip §4" . $this->getDataFolder() . "zip/" . $args[0] . "§c because it doesn't exists!");
							return true;
						}
						if(file_exists($file . ".zip")) $sender->sendMessage("§7ZIP already exists, overwriting...");
						$pathInfo = pathInfo($file);
						$parentPath = $pathInfo['dirname'];
						$dirName = $pathInfo['basename'];
						$zip = new \ZipArchive();
						$zip->open($file . ".zip", \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
						$this->rezip($sender, $file, strlen("$parentPath/"), $zip);
						$zip->close();
						$sender->sendMessage($this->prefix . "§aZIP has been successfully created on §2" . realpath("$file.zip"));
						return true;
					break;
					default:
						$sender->sendMessage("§7/file zip $args[0]§f doesn't exists!\nPlease use §7/§6file §ezip§f <§7create§f|§7unzip§f> <§7filename§f>");
						return true;
					break;
				}
			break;
			case "zlib":
			case "gz":
			case "gzip":
				if(!extension_loaded("zlib")){
					$sender->sendMessage($this->prefix . "§cZLIB extension is not loaded!");
					return true;
				}
				if(count($args) < 2 || count($args) === 0){
					$sender->sendMessage("Not enough arguments. Usage: §7/§6file §egz§f <§7create§f|§7extract§f> <§7filename§f>");
					return true;
				}
				$arg = array_shift($args);
				switch($arg){
					case "extract":
					case "uncompress":
						$file = $this->getDataFolder() . "gz/" . $args[0] . ".gz";
						if(!file_exists($file)){
							$sender->sendMessage($this->prefix . "§4" . $this->getDataFolder() . "gz/" . $args[0] . ".gz§c doesn't exists!");
							return true;
						}
						if(is_dir($this->getDataFolder() . "gz/" . $args[0])){
							$sender->sendMessage($this->prefix . "§7" . realpath($this->getDataFolder() . "gz/" . $args[0]) . "§f already exists, please delete it firstly.");
							return true;
						}
						$size = 4096;
						$gz = gzopen($file, "rb");
						$output = fopen($this->getDataFolder() . "gz/" . $args[0], "wb");
						@mkdir($this->getDataFolder() . "gz/" . $args[0]);
						while(!gzeof($gz)){
							fwrite($output, gzread($gz, $size));
						}
						fclose($output);
						gzclose($gz);
						$sender->sendMessage("§aGZIP has been successfully extracted to §2" . realpath($this->getDataFolder() . "gz/" . $args[0]));
						return true;
					break;
					case "create":
					case "make":
					case "compress":
						$file = $this->getDataFolder() . "gz/" . $args[0];
						if(!is_dir($file)){
							$sender->sendMessage($this->prefix . "§cCan't compress §4" . $this->getDataFolder() . "gz/" . $args[0] . "§c because it doesn't exists!");
							return true;
						}
						if(file_exists($file . ".gz")){
							$sender->sendMessage("§7GZIP already exists, overwriting...");
							unlink($file . ".gz");
						}
						try{
							$pathInfo = pathInfo($file);
							$parentPath = $pathInfo['dirname'];
							$dirName = $pathInfo['basename'];
							$gz = gzopen($file . ".gz", "wb9");
							$this->rezlib($sender, $file, strlen("$parentPath/"), $gz);
							gzclose($gz);
							$sender->sendMessage("§aGZIP has been successfully created on §2" . realpath("$file.gz"));
							return true;
						}catch(\Exception $e){
							$sender->sendMessage($this->prefix . "§cException found: " . $e->getMessage());
							return true;
						}
					break;
					default:
						$sender->sendMessage("§7/file zlib $args[0]§f doesn't exists!\nPlease use §7/§6file §egz§f <§7create§f|§7uncompress§f> <§7filename§f>");
						return true;
					break;
				}
			break;
			default:
				$sender->sendMessage($this->prefix . "§cThat file extension doesn't exists and/or is not available.");
				return true;
			break;
			}
		}
	}
	
	public function onDisable(){
		$this->getServer()->getLogger()->info("§cDisabling " . $this->getDescription()->getName() . "...\nThanks for using it!");
	}
	
	//rezlib() currently supports no directories
	protected function rezlib(CommandSender $sender, $file, $length, &$gz){
		$dir = opendir($file);
		while(false !== $f = readdir($dir)){
			if($f != "." and $f != ".."){
				$filePath = realpath($file . "/" . $f);
				$localPath = substr($filePath, $length);
				if($fp_in = fopen($filePath,'rb')){
					$sender->sendMessage("Adding §7" . realpath($file . "/" . $f) . "§f...");
					while (!feof($fp_in)) gzwrite($gz, fread($fp_in, 1024 * 512));
					fclose($fp_in);
				}
			}
		}
		closedir($dir);
	}
	
	protected function rezip(CommandSender $sender, $file, $length, &$zip){
		$dir = opendir($file);
		while(false !== $f = readdir($dir)){
			if($f != "." and $f != ".."){
				$filePath = realpath($file . "/" . $f);
				$localPath = substr($filePath, $length);
				if(is_file($filePath)){
					$sender->sendMessage("Adding §7$filePath§f...");
					$zip->addFile($filePath, $localPath);
				}elseif(is_dir($filePath)){
					$zip->addEmptyDir($localPath);
					$this->rezip($sender, $filePath, $length, $zip);
				}else{
					return false;
				}
			}
		}
		closedir($dir);
	}
	
	// Same as rezip()
	protected function rephar(CommandSender $sender, $file, $length, &$phar){
		$dir = opendir($file);
		while(false !== $f = readdir($dir)){
			if($f != "." and $f != ".."){
				$filePath = realpath($file . "/" . $f);
				$localPath = substr($filePath, $length);
				if(is_file($filePath)){
					$sender->sendMessage("Adding §7$filePath§f...");
					$phar->addFile($filePath, $localPath);
				}elseif(is_dir($filePath)){
					$phar->addEmptyDir($localPath);
					$this->rephar($sender, $filePath, $length, $phar);
				}else{
					return false;
				}
			}
		}
		closedir($dir);
	}
	
}