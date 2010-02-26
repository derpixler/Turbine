<?php

/**
 * Turbine
 * http://github.com/SirPepe/Turbine
 * 
 * Copyright (C) 2009 Peter Kröner, Christian Schaefer
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Library General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Load libraries
include('../../lib/base.php');
include('../../lib/browser.php');
include('../../lib/parser.php');
include('../../lib/cssp.php');
include('../../lib/cssmin.php');


// New Turbine instance
$cssp = new CSSP();


// Get and store browser properties
$browser = new Browser();


// Set global path constant SCRIPTPATH
$cssp->global_constants['SCRIPTPATH'] = dirname($_SERVER['SCRIPT_NAME']);


// Load plugins
$plugindir = '../../plugins';
if($handle = opendir($plugindir)){
	while(false !== ($pluginfile = readdir($handle))){
		if($pluginfile != '.' && $pluginfile != '..' && is_file($plugindir.'/'.$pluginfile) && pathinfo($plugindir.'/'.$pluginfile,PATHINFO_EXTENSION) == 'php' && !function_exists(substr($pluginfile, 0, -4))){
			include($plugindir.'/'.$pluginfile);
		}
	}
	closedir($handle);
}


// Precess input
if($_POST['css']){

	// Set browser properties
	$browser->name = $_POST['browser'];
	$browser->version = $_POST['browserversion'];
	$browser->family = $_POST['family'];
	$browser->familyversion = $_POST['familyversion'];
	$browser->engine = $_POST['engine'];
	$browser->engineversion = $_POST['engineversion'];
	$browser->platform = $_POST['platform'];
	$browser->platformversion = $_POST['platformversion'];
	$browser->platformtype = $_POST['platformtype'];


	// Load string
	$cssp->load_string($_POST['css'], true);


	// Set global filepath constant for the current file
	$filepath = dirname($file);
	if($filepath != '.'){
		$cssp->global_constants['FILEPATH'] = $filepath;
	}
	else{
		$cssp->global_constants['FILEPATH'] = '';
	}


	// Get plugin settings for the before parse hook
	$plugin_list = array();
	$found = false;
	foreach($cssp->css as $line){
		if(!$found){
			if(preg_match('/^[\s\t]*@turbine/i',$line) == 1){
				$found = true;
			}
		}
		else{
			preg_match('~^\s+plugins:(.*?)(?://|$)~', $line, $matches);
			if(count($matches) == 2){
				$plugin_list = $cssp->tokenize($matches[1], ',');
				break;
			}
		}
	}

	// Apply plugins
	$cssp->apply_plugins('before_parse', $plugin_list, $cssp->css);      // Apply plugins for before parse
	$cssp->parse();                                                      // Parse the code
	$cssp->apply_plugins('before_compile', $plugin_list, $cssp->parsed); // Apply plugins for before compile
	$cssp->compile();                                                    // Do the Turbine magic
	$cssp->apply_plugins('before_glue', $plugin_list, $cssp->parsed);    // Apply plugins for before glue


	// Set compression mode
	if(isset($cssp->parsed['global']['@turbine']['compress'])){
		$compress = (bool) $cssp->parsed['global']['@turbine']['compress'];
	}
	else{
		$compress = false;
	}

	// Cleanup
	unset($cssp->parsed['global']['@turbine']);                      // Remove configuration @-rule
	$output = $cssp->glue($compress);                             // Glue css output
	$cssp->apply_plugins('before_output', $plugin_list, $output); // Apply plugins for before output

	// Outpu
	echo $output;

}


?>