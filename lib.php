<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage blocktype-mendeley
 * @author     James Ballard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 James Ballard, j.ballard@ulcc.ac.uk
 *
 */

defined('INTERNAL') || die();

class PluginBlocktypeMendeley extends SystemBlocktype {

    public static function single_only() {
        return false;
    }
	
	public static function has_config() {
        return true;
    }

    public static function get_title() {
        return get_string('title', 'blocktype.mendeley');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.mendeley');
    }

    public static function get_categories() {
        return array('external');
    }
	
	public static function getInitials($name){
		//split name using spaces
		$words=explode(" ",$name);
		$inits='';
		//loop through array extracting initial letters
			foreach($words as $word){
			$inits.=strtoupper(substr($word,0,1));
			}
		return $inits;	
	}

    public static function render_instance(BlockInstance $instance, $editing=false) {
		$configdata = $instance->get('configdata');
		require_once(get_config('docroot') . 'blocktype/mendeley/mendeley.php');
		$mendeley = new Mendeley();
		
		if(!empty($configdata['folder']) && $result = $mendeley->get('folders/'.$configdata['folder'])) { // get the folder record from Mendeley
		
			$documents = array();
			
			$i = 0;
			foreach ($result->document_ids as $doc) {
				/* Different document types contain different fields so we need to make sure that 
				 * empty records are replaced with '' strings so that the template can process them. 
				 * 
				 * Also some fields require formatting into correct referencing - I have used APA.
				 * Follow the links to Mendeley site to get other formats.
				 *
				 * TO DO: write a display library and template and allow for different reference formats
				 */ 
				 
				/* Create empty field sets */
				$documents[$i]['authors'] = array();
				$documents[$i]['title'] = '';
				$documents[$i]['mendeley_url'] = ''; 
				$documents[$i]['published_in'] = '';
				$documents[$i]['volume'] = ''; 
				$documents[$i]['issue'] = '';  
				$documents[$i]['year'] = ''; 
				
				// Get the document details from Mendely
				$document = $mendeley->get('documents/'.$doc);

				//Populate the fields where they exist - different fields are relevant to different report types. 
				//TO DO: this should really sort documents by their authors alphabetically
				$documents[$i]['type'] = $document->type;
				if(!empty($document->authors)) {
					$a = 0;
					foreach($document->authors as $author) {
						$initials = self::getInitials($author->forename);
						$documents[$i]['authors'][$a] = array('surname'=>$author->surname,'initials'=>$initials);
						$a++;
					}
				}
				if(!empty($document->title)) {
					$documents[$i]['title'] = $document->title;
				}
				if(!empty($document->mendeley_url)) {
					$documents[$i]['mendeley_url'] = $document->mendeley_url;
				}
				if(!empty($document->published_in)) {
					$documents[$i]['published_in'] = $document->published_in;
				}
				if(!empty($document->volume)) {
					$documents[$i]['volume'] = $document->volume;
				}
				if(!empty($document->issue)) {
					$documents[$i]['issue'] = $document->issue;
				}
				if(!empty($document->year)) {
					$documents[$i]['year'] = $document->year;
				}
				$i++;
			}
			
			// send new document record to the template
			$smarty = smarty_core();
			$smarty->assign('documents', $documents);
			return $smarty->fetch('blocktype:mendeley:apa.tpl');
		}else{
			$smarty = smarty_core();
			return $smarty->fetch('blocktype:mendeley:notavailable.tpl');
		}
    }

    public static function has_instance_config() {
        return true;
    }

    public static function default_copy_type() {
        return 'full';
    }
	
	public static function get_config_options() {
        $elements = array();
        $elements['apiconfigfieldset'] = array(
            'type' => 'fieldset',
            'legend' => get_string('apiconfig', 'blocktype.mendeley'),
            'elements' => array(
                'apiconfigdescription' => array(
                    'value' => get_string('apiconfigdesc', 'blocktype.mendeley')
                ),
                'key' => array(
					'type'  => 'text',
					'title' => get_string('key', 'blocktype.mendeley'),
					'defaultvalue' => get_config_plugin('blocktype', 'mendeley', 'key'),
				),
				'secret' => array(
					'type'  => 'text',
					'title' => get_string('secret', 'blocktype.mendeley'),
					'defaultvalue' => get_config_plugin('blocktype', 'mendeley', 'secret'),
            	)
            ),
        );
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'mendeley', 'key', $values['key']);
		set_config_plugin('blocktype', 'mendeley', 'secret', $values['secret']);
    }
	
	public static function instance_config_form($instance) {
        $configdata = $instance->get('configdata');

        $form = array();

        require_once(get_config('docroot') . 'blocktype/mendeley/mendeley.php');
		$mendeley = new Mendeley();
		// GET request to look up things
		$result = $mendeley->get('folders'); // returns a PHP object with all documents of the group
		
        $options = array(
            0 => get_string('dontshowemail', 'blocktype.internal/contactinfo'),
        );

        foreach ($result as $folder) {
            $options[$folder->id] = $folder->name;
        }

        $form['folder'] = array(
            'type'    => 'radio',
            'title'   => get_string('folder', 'blocktype.mendeley'),
            'options' => $options,
            'defaultvalue' => (isset($configdata['folder'])) ? $configdata['folder'] : 0,
            'separator' => '<br>',
        );

        return $form;
    }
}

?>