<?php
class ModelExtensionMyparcelnlInit extends Model
{
    function installDatabase()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "myparcel_shipment
        (
            `order_id` int(11) NOT NULL AUTO_INCREMENT,
            `export_settings` text,
            `shipment_data` text,
            `delivery_options` text,
            `signed` int(1),
            `recipient_only` int(1),
            `extra_options` text,
            `prices` text,
            PRIMARY KEY (`order_id`)
        );");
        //add  field to  *_myparcel_shipment table
        $query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name =  '". DB_PREFIX ."myparcel_shipment' AND table_schema = '". DB_DATABASE ."' AND column_name = 'external_id'");
        if(count($query->row) == 0 ){
            $this->db->query("ALTER TABLE `" .DB_PREFIX. "myparcel_shipment` ADD COLUMN `external_id` VARCHAR(50) NULL DEFAULT NULL;");
        }
        $query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name =  '". DB_PREFIX ."myparcel_shipment' AND table_schema = '". DB_DATABASE ."' AND column_name = 'type'");
        if(count($query->row) == 0 ){
            $this->db->query("ALTER TABLE `" .DB_PREFIX. "myparcel_shipment` ADD COLUMN `type` VARCHAR(50) NULL DEFAULT NULL;");
        }
    }

    function installMyParcelTotal()
    {
        $total_name = 'myparcel_total';
        $this->load->model('user/user_group');

        /** Backward compatibility for Opencart 2.0.0.0 **/
        if(version_compare(VERSION, '3.0.0.0', '>=')) {
            $this->load->model('setting/extension');
            $this->model_setting_extension->install('total', $total_name);

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/total/' . $total_name);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/total/' . $total_name);
            $this->load->controller('extension/total/' . $total_name . '/install');
        } else if(version_compare(VERSION, '2.3.0.0', '>=')) {
            $this->load->model('extension/extension');
            $this->model_extension_extension->install('total', $total_name);

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/total/' . $total_name);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/total/' . $total_name);
            $this->load->controller('extension/total/' . $total_name . '/install');
        } elseif (version_compare(VERSION, '2.0.0.0', '>=')) {
            $this->load->model('extension/extension');
            $this->model_extension_extension->install('total', $total_name);

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'total/' . $total_name);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'total/' . $total_name);
            $this->load->controller('total/' . $total_name . '/install');
        } else {
            // Opencart 1x
            $this->load->model('setting/extension');
            $this->model_setting_extension->install('total', $total_name);

            $this->model_user_user_group->addPermission($this->user->getId(), 'access', 'total/' . $total_name);
            $this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'total/' . $total_name);
        }
    }

    function installMyParcelShipping()
    {
        $shipping_name = 'myparcel_shipping';
        $this->load->model('user/user_group');

        /** Backward compatibility for Opencart 2.0.0.0 **/
        if(version_compare(VERSION, '2.3.0.0', '>=')) {
            $this->load->model('setting/extension');
            //$this->model_setting_extension->install('shipping', $shipping_name);

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/shipping/' . $shipping_name);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/shipping/' . $shipping_name);
            $this->load->controller('extension/shipping/' . $shipping_name . '/install');
        } else if(version_compare(VERSION, '2.3.0.0', '>=')) {
            $this->load->model('extension/extension');
            //$this->model_extension_extension->install('shipping', $shipping_name);

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/shipping/' . $shipping_name);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/shipping/' . $shipping_name);
            $this->load->controller('extension/shipping/' . $shipping_name . '/install');
        } elseif (version_compare(VERSION, '2.0.0.0', '>=')) {
            $this->load->model('extension/extension');
            //$this->model_extension_extension->install('shipping', $shipping_name);

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'shipping/' . $shipping_name);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'shipping/' . $shipping_name);
            $this->load->controller('shipping/' . $shipping_name . '/install');
        } else {
            // Opencart 1x
            $this->load->model('setting/extension');
            //$this->model_setting_extension->install('shipping', $shipping_name);

            $this->model_user_user_group->addPermission($this->user->getId(), 'access', 'shipping/' . $shipping_name);
            $this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'shipping/' . $shipping_name);
        }
    }

    function uninstallMyParcelShipping()
    {
        $this->request->get['extension'] = 'myparcel_shipping';
        /** Backward compatibility for Opencart 1.0.0.0 **/
        if(version_compare(VERSION, '3.0.0.0', '>=')) {
            $this->load->model('setting/extension');
            $this->load->model('setting/setting');
            $this->model_setting_extension->uninstall('shipping', $this->request->get['extension']);
            $this->model_setting_setting->deleteSetting('myparcel_shipping');
        } else if(version_compare(VERSION, '2.0.0.0', '>=')) {
            $this->load->model('extension/extension');
            $this->model_extension_extension->uninstall('shipping', $this->request->get['extension']);
        } else {
            $this->load->model('setting/extension');
            $this->load->model('setting/setting');
            $this->model_setting_extension->uninstall('shipping', $this->request->get['extension']);
            $this->model_setting_setting->deleteSetting('myparcel_shipping');
        }
    }

    function uninstallMyParcelTotal()
    {
        $this->request->get['extension'] = 'myparcel_total';
        /** Backward compatibility for Opencart 1.0.0.0 **/
        if(version_compare(VERSION, '3.0.0.0', '>=')) {
            $this->load->model('setting/extension');
            $this->load->model('setting/setting');
            $this->model_setting_extension->uninstall('total', $this->request->get['extension']);
            $this->model_setting_setting->deleteSetting('myparcel_total');
        } else if(version_compare(VERSION, '2.0.0.0', '>=')) {
            $this->load->model('extension/extension');
            $this->model_extension_extension->uninstall('total', $this->request->get['extension']);
        } else {
            $this->load->model('setting/extension');
            $this->load->model('setting/setting');
            $this->model_setting_extension->uninstall('total', $this->request->get['extension']);
            $this->model_setting_setting->deleteSetting('myparcel_total');
        }
    }

    function refreshOcmod()
    {
        $this->load->model('setting/modification');

        // Just before files are deleted, if config settings say maintenance mode is off then turn it on
        $maintenance = $this->config->get('config_maintenance');

        $this->load->model('setting/setting');

        $this->model_setting_setting->editSettingValue('config', 'config_maintenance', true);

        // Clear all modification files
        $files = array();

        // Make path into an array
        $path = array(DIR_MODIFICATION . '*');

        // While the path array is still populated keep looping through
        while (count($path) != 0) {
            $next = array_shift($path);

            foreach (glob($next) as $file) {
                // If directory add to path array
                if (is_dir($file)) {
                    $path[] = $file . '/*';
                }

                // Add the file to the files to be deleted array
                $files[] = $file;
            }
        }

        // Reverse sort the file array
        rsort($files);

        // Clear all modification files
        foreach ($files as $file) {
            if ($file != DIR_MODIFICATION . 'index.html') {
                // If file just delete
                if (is_file($file)) {
                    unlink($file);

                    // If directory use the remove directory function
                } elseif (is_dir($file)) {
                    rmdir($file);
                }
            }
        }

        // Begin
        $xml = array();

        // Load the default modification XML
        $xml[] = file_get_contents(DIR_SYSTEM . 'modification.xml');

        // This is purly for developers so they can run mods directly and have them run without upload sfter each change.
        $files = glob(DIR_SYSTEM . '*.ocmod.xml');

        if ($files) {
            foreach ($files as $file) {
                $xml[] = file_get_contents($file);
            }
        }

        // Get the default modification file
        $results = $this->model_setting_modification->getModifications();

        foreach ($results as $result) {
            if ($result['status']) {
                $xml[] = $result['xml'];
            }
        }

        $modification = array();

        foreach ($xml as $xml) {
            if (empty($xml)){
                continue;
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->loadXml($xml);

            // Wipe the past modification store in the backup array
            $recovery = array();

            // Set the a recovery of the modification code in case we need to use it if an abort attribute is used.
            if (isset($modification)) {
                $recovery = $modification;
            }

            $files = $dom->getElementsByTagName('modification')->item(0)->getElementsByTagName('file');

            foreach ($files as $file) {
                $operations = $file->getElementsByTagName('operation');

                $files = explode('|', $file->getAttribute('path'));

                foreach ($files as $file) {
                    $path = '';

                    // Get the full path of the files that are going to be used for modification
                    if ((substr($file, 0, 7) == 'catalog')) {
                        $path = DIR_CATALOG . substr($file, 8);
                    }

                    if ((substr($file, 0, 5) == 'admin')) {
                        $path = DIR_APPLICATION . substr($file, 6);
                    }

                    if ((substr($file, 0, 6) == 'system')) {
                        $path = DIR_SYSTEM . substr($file, 7);
                    }

                    if ($path) {
                        $files = glob($path, GLOB_BRACE);

                        if ($files) {
                            foreach ($files as $file) {
                                // Get the key to be used for the modification cache filename.
                                if (substr($file, 0, strlen(DIR_CATALOG)) == DIR_CATALOG) {
                                    $key = 'catalog/' . substr($file, strlen(DIR_CATALOG));
                                }

                                if (substr($file, 0, strlen(DIR_APPLICATION)) == DIR_APPLICATION) {
                                    $key = 'admin/' . substr($file, strlen(DIR_APPLICATION));
                                }

                                if (substr($file, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
                                    $key = 'system/' . substr($file, strlen(DIR_SYSTEM));
                                }

                                // If file contents is not already in the modification array we need to load it.
                                if (!isset($modification[$key])) {
                                    $content = file_get_contents($file);

                                    $modification[$key] = preg_replace('~\r?\n~', "\n", $content);
                                    $original[$key] = preg_replace('~\r?\n~', "\n", $content);
                                }

                                foreach ($operations as $operation) {
                                    $error = $operation->getAttribute('error');

                                    // Ignoreif
                                    $ignoreif = $operation->getElementsByTagName('ignoreif')->item(0);

                                    if ($ignoreif) {
                                        if ($ignoreif->getAttribute('regex') != 'true') {
                                            if (strpos($modification[$key], $ignoreif->textContent) !== false) {
                                                continue;
                                            }
                                        } else {
                                            if (preg_match($ignoreif->textContent, $modification[$key])) {
                                                continue;
                                            }
                                        }
                                    }

                                    $status = false;

                                    // Search and replace
                                    if ($operation->getElementsByTagName('search')->item(0)->getAttribute('regex') != 'true') {
                                        // Search
                                        $search = $operation->getElementsByTagName('search')->item(0)->textContent;
                                        $trim = $operation->getElementsByTagName('search')->item(0)->getAttribute('trim');
                                        $index = $operation->getElementsByTagName('search')->item(0)->getAttribute('index');

                                        // Trim line if no trim attribute is set or is set to true.
                                        if (!$trim || $trim == 'true') {
                                            $search = trim($search);
                                        }

                                        // Add
                                        $add = $operation->getElementsByTagName('add')->item(0)->textContent;
                                        $trim = $operation->getElementsByTagName('add')->item(0)->getAttribute('trim');
                                        $position = $operation->getElementsByTagName('add')->item(0)->getAttribute('position');
                                        $offset = $operation->getElementsByTagName('add')->item(0)->getAttribute('offset');

                                        if ($offset == '') {
                                            $offset = 0;
                                        }

                                        // Trim line if is set to true.
                                        if ($trim == 'true') {
                                            $add = trim($add);
                                        }

                                        // Check if using indexes
                                        if ($index !== '') {
                                            $indexes = explode(',', $index);
                                        } else {
                                            $indexes = array();
                                        }

                                        // Get all the matches
                                        $i = 0;

                                        $lines = explode("\n", $modification[$key]);

                                        for ($line_id = 0; $line_id < count($lines); $line_id++) {
                                            $line = $lines[$line_id];

                                            // Status
                                            $match = false;

                                            // Check to see if the line matches the search code.
                                            if (stripos($line, $search) !== false) {
                                                // If indexes are not used then just set the found status to true.
                                                if (!$indexes) {
                                                    $match = true;
                                                } elseif (in_array($i, $indexes)) {
                                                    $match = true;
                                                }

                                                $i++;
                                            }

                                            // Now for replacing or adding to the matched elements
                                            if ($match) {
                                                switch ($position) {
                                                    default:
                                                    case 'replace':
                                                        $new_lines = explode("\n", $add);

                                                        if ($offset < 0) {
                                                            array_splice($lines, $line_id + $offset, abs($offset) + 1, array(str_replace($search, $add, $line)));

                                                            $line_id -= $offset;
                                                        } else {
                                                            array_splice($lines, $line_id, $offset + 1, array(str_replace($search, $add, $line)));
                                                        }

                                                        break;
                                                    case 'before':
                                                        $new_lines = explode("\n", $add);

                                                        array_splice($lines, $line_id - $offset, 0, $new_lines);

                                                        $line_id += count($new_lines);
                                                        break;
                                                    case 'after':
                                                        $new_lines = explode("\n", $add);

                                                        array_splice($lines, ($line_id + 1) + $offset, 0, $new_lines);

                                                        $line_id += count($new_lines);
                                                        break;
                                                }

                                                $status = true;
                                            }
                                        }

                                        $modification[$key] = implode("\n", $lines);
                                    } else {
                                        $search = trim($operation->getElementsByTagName('search')->item(0)->textContent);
                                        $limit = $operation->getElementsByTagName('search')->item(0)->getAttribute('limit');
                                        $replace = trim($operation->getElementsByTagName('add')->item(0)->textContent);

                                        // Limit
                                        if (!$limit) {
                                            $limit = -1;
                                        }

                                        // Log
                                        $match = array();

                                        preg_match_all($search, $modification[$key], $match, PREG_OFFSET_CAPTURE);

                                        // Remove part of the the result if a limit is set.
                                        if ($limit > 0) {
                                            $match[0] = array_slice($match[0], 0, $limit);
                                        }

                                        if ($match[0]) {
                                            $status = true;
                                        }

                                        // Make the modification
                                        $modification[$key] = preg_replace($search, $replace, $modification[$key], $limit);
                                    }

                                    if (!$status) {
                                        // Abort applying this modification completely.
                                        if ($error == 'abort') {
                                            $modification = $recovery;
                                            // Log
                                            break 5;
                                        }
                                        // Skip current operation or break
                                        elseif ($error == 'skip') {
                                            continue;
                                        }
                                        // Break current operations
                                        else {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Write all modification files
        foreach ($modification as $key => $value) {
            // Only create a file if there are changes
            if ($original[$key] != $value) {
                $path = '';

                $directories = explode('/', dirname($key));

                foreach ($directories as $directory) {
                    $path = $path . '/' . $directory;

                    if (!is_dir(DIR_MODIFICATION . $path)) {
                        @mkdir(DIR_MODIFICATION . $path, 0777);
                    }
                }

                $handle = fopen(DIR_MODIFICATION . $key, 'w');

                fwrite($handle, $value);

                fclose($handle);
            }
        }

        // Maintance mode back to original settings
        $this->model_setting_setting->editSettingValue('config', 'config_maintenance', $maintenance);
    }
}
