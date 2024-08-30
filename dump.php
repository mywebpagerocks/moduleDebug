<?php

/**
 * Module Debug Dump Script
 * 
 * This script zips the specified module folder and includes a full database dump (structure and data)
 * for all tables associated with the module, including relevant entries from the configuration tables.
 * It reads the database configuration directly from PrestaShop's settings.
 * 
 * Usage: Access via browser with ?module=modulename
 * Example: http://your-prestashop-site/module_debug_dump.php?module=modulename
 */

require_once('../../config/config.inc.php');
require_once('../../config/defines.inc.php');
require_once('../../init.php');

// Helper function to display the HTML form with Bootstrap styling and module selection
function displayForm($defaultDumpName, $modules)
{
    echo '<html>
            <head>
                <title>Module Debug Dump Script</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    .spinner-border {
                        display: none;
                        width: 1.5rem;
                        height: 1.5rem;
                        border-width: 0.2em;
                    }
                    .overlay {
                        display: none;
                        position: fixed;
                        width: 100%;
                        height: 100%;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-color: rgba(0, 0, 0, 0.5);
                        z-index: 9999;
                    }
                    .overlay-content {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: white;
                        font-size: 1.5rem;
                        text-align: center;
                    }
                </style>
                <script>
                    function updateDumpName() {
                        var moduleName = document.getElementById("moduleName").value;
                        var now = new Date();
                        var year = now.getFullYear();
                        var month = String(now.getMonth() + 1).padStart(2, "0");
                        var day = String(now.getDate()).padStart(2, "0");
                        var hours = String(now.getHours()).padStart(2, "0");
                        var minutes = String(now.getMinutes()).padStart(2, "0");
                        var seconds = String(now.getSeconds()).padStart(2, "0");
                        var datetime = year + "-" + month + "-" + day + "_" + hours + "-" + minutes + "-" + seconds;
                        document.getElementById("dumpName").value = moduleName + "-dump-" + datetime;
                    }

                    function handleFormSubmit(event) {
                        var generateButton = document.getElementById("generateButton");
                        var spinner = document.getElementById("spinner");
                        var overlay = document.getElementById("overlay");
                        var buttonText = document.getElementById("buttonText");

                        generateButton.disabled = true;
                        spinner.style.display = "inline-block";
                        overlay.style.display = "block";
                        buttonText.textContent = " Generating...";
                    }

                    function handleCleanup() {
                        var checkboxes = document.querySelectorAll(".dump-checkbox:checked");
                        var filesToDelete = [];
                        
                        checkboxes.forEach(function(checkbox) {
                            filesToDelete.push(checkbox.value);
                        });

                        if (filesToDelete.length > 0) {
                            var xhr = new XMLHttpRequest();
                            xhr.open("POST", "", true);
                            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4 && xhr.status === 200) {
                                    location.reload(); // Reload the page to update the list
                                }
                            };
                            xhr.send("cleanup=true&files=" + encodeURIComponent(JSON.stringify(filesToDelete)));
                        } else {
                            alert("No files selected for cleanup.");
                        }
                    }
                </script>
            </head>
            <body class="bg-light">
                <div class="container">
                    <div class="py-5 text-center">
                        <h2>Generate Debug Data</h2>
                        <p class="lead">Select a module, enter your debug note, and customize the dump name. Then click "Generate Debug Data" to create a debug package.</p>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <form method="POST" class="card p-4" onsubmit="handleFormSubmit(event)">
                                <div class="mb-3">
                                    <label for="moduleName" class="form-label">Select Module</label>
                                    <select id="moduleName" name="moduleName" class="form-select" onchange="updateDumpName()" required>
                                        <option value="" disabled selected>Select a module</option>';

    foreach ($modules as $module) {
        echo '<option value="' . htmlspecialchars($module) . '">' . htmlspecialchars($module) . '</option>';
    }

    echo '                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="debugNote" class="form-label">Enter Debug Note</label>
                                    <textarea id="debugNote" name="debugNote" class="form-control" rows="5" 
placeholder="(Optional)
https://app.asana.com/0/12345...
Sposób odtworzenia...
Wejdź tam...
Wybierz to..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="dumpName" class="form-label">Enter Dump Name</label>
                                    <input type="text" id="dumpName" name="dumpName" class="form-control" value="' . htmlspecialchars($defaultDumpName) . '" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" id="generateButton" class="btn btn-primary btn-lg">
                                        <span id="spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        <span id="buttonText">Generate Debug Data</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>';

    $outputDir = __DIR__ . '/output';
    $dumps = array_filter(scandir($outputDir), function ($item) use ($outputDir) {
        return is_file($outputDir . '/' . $item) && pathinfo($item, PATHINFO_EXTENSION) === 'zip';
    });

    // List existing dumps
    echo '<div class="row justify-content-center mt-5">
        <div class="col-md-8">';

    $outputDir = __DIR__ . '/output';
    $dumps = array_filter(scandir($outputDir), function ($item) use ($outputDir) {
        return is_file($outputDir . '/' . $item) && pathinfo($item, PATHINFO_EXTENSION) === 'zip';
    });

    if (count($dumps) > 0) {
        echo '<form method="POST" id="cleanupForm">
            <h4>Existing Dumps</h4>
            <ul class="list-group">';

        foreach ($dumps as $dump) {
            $fileSizeInBytes = filesize($outputDir . '/' . $dump);
            $fileSizeInMB = $fileSizeInBytes / (1024 * 1024); // Convert bytes to MB

            echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" class="form-check-input me-2 dump-checkbox" value="' . htmlspecialchars($dump) . '" checked>
                    <a href="output/' . htmlspecialchars($dump) . '" download>' . htmlspecialchars($dump) . '</a>
                </div>
                <span class="badge bg-primary rounded-pill">' . number_format($fileSizeInMB, 2) . ' MB</span>
              </li>';
        }

        echo '  </ul>
            <button type="button" class="btn btn-danger mt-3" onclick="handleCleanup()">Cleanup Selected</button>
          </form>';
    } else {
        echo '<div class="alert alert-info mt-4" role="alert">
            <h4 class="alert-heading">No Dumps Available</h4>
            <p>There are currently no debug dumps available. Please generate a new debug data package to see it listed here.</p>
          </div>';
    }

    echo '    </div>
      </div>';


    echo '    </div>
          </div>';

    echo '  <div id="overlay" class="overlay">
                <div class="overlay-content">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Processing, please wait...</div>
                </div>
            </div>';

    echo '</div>
        </body>
    </html>';
}

// Success page with Bootstrap styling
function displaySuccessPage($zipFilename)
{
    $context = Context::getContext();
    $ssl = Configuration::get('PS_SSL_ENABLED');
    $zipUrl = $context->link->getBaseLink($context->shop->id, $ssl) . 'tools/moduleDebug/output/' . $zipFilename . '.zip';
    echo '<html>
            <head>
                <title>Success - Module Debug Dump</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container">
                    <div class="py-5 text-center">
                        <h2>Debug Data Package Created Successfully</h2>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card p-4">
                                <p class="lead">Your debug data package has been created successfully.</p>
                                <a href="' . htmlspecialchars($zipUrl) . '" class="btn btn-success btn-lg mb-3">Download ' . htmlspecialchars(basename($zipFilename)) . '</a>
                                <a href="dump.php" class="btn btn-secondary btn-lg">Reset</a>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
        </html>';
}


// Generate the debug data package
function generateDebugData($moduleName, $debugNote, $dumpName, $phpinfoFile)
{
    $prefix = getModulePrefix($moduleName);
    $modulePath = _PS_MODULE_DIR_ . $moduleName;
    $relatedTables = getRelatedTables($modulePath);
    $dumpFilename = createDatabaseDump($moduleName, $relatedTables, $prefix);

    $zipFilename = __DIR__ . "/output/{$dumpName}.zip";
    $moduleZipFilename = __DIR__ . "/output/{$moduleName}.zip"; // Zip file for the module

    // Zip the module contents into a separate zip file
    $moduleZip = new ZipArchive();
    if ($moduleZip->open($moduleZipFilename, ZipArchive::CREATE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($modulePath) + 1);
                $moduleZip->addFile($filePath, $relativePath);
            }
        }

        $moduleZip->close();
    } else {
        echo '<div class="alert alert-danger mt-4">Failed to create module zip file.</div>';
        return;
    }

    // Create the final zip file containing all components
    $finalZip = new ZipArchive();
    if ($finalZip->open($zipFilename, ZipArchive::CREATE) === TRUE) {
        $finalZip->addFile($phpinfoFile, basename($phpinfoFile));
        if (file_exists($dumpFilename)) {
            $finalZip->addFile($dumpFilename, basename($dumpFilename));
        }
        $finalZip->addFile($moduleZipFilename, basename($moduleZipFilename)); // Add the zipped module

        $finalZip->close();

        // Cleanup temporary files
        if (file_exists($dumpFilename)) {
            unlink($dumpFilename);
        }
        if (file_exists($phpinfoFile)) {
            unlink($phpinfoFile);
        }
        if (file_exists($moduleZipFilename)) {
            unlink($moduleZipFilename); // Cleanup the module zip file
        }

        displaySuccessPage($dumpName);
    } else {
        echo '<div class="alert alert-danger mt-4">Failed to create final zip file.</div>';
    }
}

function createPhpInfoFile($userNote, $moduleName) {
    $separator = str_repeat('-', 50);

    // Gather PrestaShop Information
    $prestashopInfo = [
        'PrestaShop Version' => _PS_VERSION_,
        'Shop URL' => Tools::getShopDomain(true),
        'Shop Path' => _PS_ROOT_DIR_,
        'Current Theme' => Context::getContext()->shop->theme->getName(),
        'SSL Enabled' => Configuration::get('PS_SSL_ENABLED') ? 'Yes' : 'No',
        'Cache Enabled' => (Configuration::get('PS_SMARTY_CACHE') || Configuration::get('PS_CACHE_ENABLED')) ? 'Yes' : 'No',
        'Installed Languages' => implode(', ', array_column(Language::getLanguages(), 'name')),
        'Multishop Enabled' => Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') ? 'Yes' : 'No'
    ];

    if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
        $shops = Shop::getShops();
        $shopDetails = [];
        foreach ($shops as $shop) {
            $shopDetails[] = $shop['name'] . ' (' . $shop['id_shop'] . ')';
        }
        $prestashopInfo['Shops'] = implode(', ', $shopDetails);
    }

    // Gather Server Information
    $serverInfo = [
        'Server OS' => php_uname(),
        'Server Software' => $_SERVER['SERVER_SOFTWARE'],
        'PHP Version' => phpversion(),
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time'),
        'Upload Max File Size' => ini_get('upload_max_filesize')
    ];

    // Gather Database Information using PrestaShop's Db class
    $db = Db::getInstance();
    $databaseType = get_class($db) === 'DbPDO' ? 'PDO MySQL' : (get_class($db) === 'DbMySQLi' ? 'MySQLi' : 'Unknown');

    $databaseInfo = [
        'Database Type' => $databaseType,
        'MySQL Version' => $db->getVersion(),
        'MySQL Server' => Configuration::get('PS_DB_SERVER'),
        'MySQL Name' => _DB_NAME_,
        'MySQL User' => _DB_USER_,
        'Tables Prefix' => _DB_PREFIX_,
        'MySQL Engine' => $db->getValue("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . _DB_NAME_ . "'")
    ];

    // Gather Mail Configuration
    $mailConfig = [
        'Mail Method' => Configuration::get('PS_MAIL_METHOD') == 2 ? 'SMTP' : 'PHP mail()',
        'SMTP Server' => Configuration::get('PS_MAIL_SERVER'),
        'SMTP User' => Configuration::get('PS_MAIL_USER')
    ];

    // List of Installed Modules
    $modules = Module::getModulesInstalled();
    $installedModules = [];
    foreach ($modules as $module) {
        $installedModules[] = $module['name'] . ' - v' . $module['version'];
    }

    // List of Overrides
    $overrideFiles = [];
    $overridePaths = [ _PS_OVERRIDE_DIR_ . 'classes/', _PS_OVERRIDE_DIR_ . 'controllers/'];
    foreach ($overridePaths as $path) {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    // Exclude index.php files
                    if (basename($filePath) !== 'index.php') {
                        $overrideFiles[] = str_replace(_PS_ROOT_DIR_ . '/', '', $filePath);
                    }
                }
            }
        }
    }

    // Capture phpinfo() output
    ob_start();
    phpinfo();
    $phpinfoOutput = ob_get_clean();

    // Start building the content for the phpinfo.html file
    $phpInfoContent = "<html><head><title>Diagnostic Information</title></head><body>";
    $phpInfoContent .= "<div class=\"center\"><h1>User's Debug Note</h1><div style=\"width: 934px;margin: 0 auto;padding: 20px;background: #eee;\">" . htmlspecialchars($userNote) . "</div>";

    $phpInfoContent .= "<h2>PrestaShop Information</h2><table border='1' cellpadding='5'>";
    foreach ($prestashopInfo as $key => $value) {
        $phpInfoContent .= "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    $phpInfoContent .= "</table>";

    $phpInfoContent .= "<h2>Server Information</h2><table border='1' cellpadding='5'>";
    foreach ($serverInfo as $key => $value) {
        $phpInfoContent .= "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    $phpInfoContent .= "</table>";

    $phpInfoContent .= "<h2>Database Information</h2><table border='1' cellpadding='5'>";
    foreach ($databaseInfo as $key => $value) {
        $phpInfoContent .= "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    $phpInfoContent .= "</table>";

    $phpInfoContent .= "<h2>Mail Configuration</h2><table border='1' cellpadding='5'>";
    foreach ($mailConfig as $key => $value) {
        $phpInfoContent .= "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    $phpInfoContent .= "</table>";

    $phpInfoContent .= "<h2>Installed Modules</h2><table border='1' cellpadding='5'>";
    foreach ($installedModules as $module) {
        $phpInfoContent .= "<tr><td>$module</td></tr>";
    }
    $phpInfoContent .= "</table>";

    $phpInfoContent .= "<h2>List of Overrides</h2><table border='1' cellpadding='5'>";
    foreach ($overrideFiles as $override) {
        $phpInfoContent .= "<tr><td>$override</td></tr>";
    }
    $phpInfoContent .= "</table>";

    $phpInfoContent .= "<h2>PHP Info</h2>" . $phpinfoOutput;
    $phpInfoContent .= "</div></body></html>";

    // Generate the filename
    $datetime = date('Y-m-d_H-i-s');
    $phpinfoFilename = __DIR__ . "/output/diagnostic_information_{$moduleName}_{$datetime}.html";
    file_put_contents($phpinfoFilename, $phpInfoContent);

    return $phpinfoFilename; // Return the filename for reference
}

// Get the list of available modules (directories only)
function getModuleList()
{
    $moduleDir = _PS_MODULE_DIR_;
    $modules = array_filter(scandir($moduleDir), function ($item) use ($moduleDir) {
        return is_dir($moduleDir . $item) && $item !== '.' && $item !== '..';
    });
    return $modules;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cleanup']) && isset($_POST['files'])) {
        $files = json_decode($_POST['files'], true);
        $outputDir = __DIR__ . '/output';

        foreach ($files as $file) {
            $filePath = $outputDir . '/' . $file;
            if (is_file($filePath)) {
                unlink($filePath); // Delete the file
            }
        }

        // After deletion, return success status
        echo 'Cleanup successful';
        exit;
    }

    // Existing logic for generating debug data
    if (isset($_POST['moduleName']) && isset($_POST['debugNote']) && isset($_POST['dumpName'])) {
        $moduleName = trim($_POST['moduleName']);
        $debugNote = trim($_POST['debugNote']);
        $phpinfoFile = createPhpInfoFile($debugNote, $moduleName);
        $dumpName = trim($_POST['dumpName']);

        if (is_dir(_PS_MODULE_DIR_ . $moduleName)) {
            generateDebugData($moduleName, $debugNote, $dumpName, $phpinfoFile);
        } else {
            echo '<div class="alert alert-danger mt-4">Module folder does not exist.</div>';
            $modules = getModuleList();
            displayForm($dumpName, $modules);
        }
    } else {
        echo '<div class="alert alert-danger mt-4">Module name, dump name, or debug note is missing.</div>';
        $modules = getModuleList();
        displayForm($dumpName, $modules);
    }
} else {
    // Initial page load: display the form and existing dumps
    $modules = getModuleList();
    $datetime = date('Y-m-d_H-i-s');
    $defaultDumpName = "module-dump-{$datetime}";
    displayForm($defaultDumpName, $modules);
}


function zipModuleFolderWithDump($moduleName, $dumpFilename)
{
    $modulePath = _PS_MODULE_DIR_ . $moduleName;
    $zipFilename = __DIR__ . "/output/$moduleName.zip";

    $zip = new ZipArchive();
    if ($zip->open($zipFilename, ZipArchive::CREATE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($modulePath) + 1);
                $zip->addFile($filePath, $moduleName . '/' . $relativePath);
            }
        }

        if (file_exists($dumpFilename)) {
            $zip->addFile($dumpFilename, basename($dumpFilename));
        }

        $zip->close();
    } else {
        echo '<div class="alert alert-danger mt-4">Failed to create module zip file.</div>';
    }
}

function getRelatedTables($modulePath)
{
    $tables = [];

    // Recursively find all PHP files in the module directory
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulePath));
    $phpFiles = new RegexIterator($files, '/\.php$/');

    foreach ($phpFiles as $file) {
        $content = file_get_contents($file->getRealPath());

        // Check if the file contains a class that extends ObjectModel
        if (strpos($content, 'extends ObjectModel') !== false) {
            // Use regex to find the $definition array and extract the table name
            if (preg_match('/public\s+static\s+\$definition\s*=\s*\[.*?\'table\'\s*=>\s*\'([^\']+)\'/s', $content, $matches)) {
                $table = _DB_PREFIX_ . $matches[1];
                $tables[] = $table;
                $tables[] = $table . '_lang';
                $tables[] = $table . '_shop';
            }
        }
    }

    // Remove any duplicates
    $tables = array_unique($tables);

    return $tables;
}

function getLanguageMapping()
{
    $db = Db::getInstance();
    $languages = $db->executeS("SELECT `id_lang`, `iso_code` FROM `" . _DB_PREFIX_ . "lang`");

    $langMapping = [];
    foreach ($languages as $language) {
        $langMapping[$language['iso_code']] = $language['id_lang'];
    }

    return $langMapping;
}

function createDatabaseDump($moduleName, $tables, $prefix)
{
    $outputDir = __DIR__ . '/output';
    $dumpFilename = $outputDir . "/db_dump_" . $moduleName . ".sql";
    $db = Db::getInstance();
    $dumpFile = fopen($dumpFilename, 'w');

    fwrite($dumpFile, "DELETE FROM `" . _DB_PREFIX_ . "configuration_lang` WHERE id_configuration IN (SELECT id_configuration FROM `" . _DB_PREFIX_ . "configuration` WHERE `name` LIKE '$prefix%');\n");
    fwrite($dumpFile, "DELETE FROM `" . _DB_PREFIX_ . "configuration` WHERE `name` LIKE '$prefix%';\n\n");

    foreach (array_reverse($tables) as $table) {
        fwrite($dumpFile, "DROP TABLE IF EXISTS `$table`;\n");
    }

    foreach ($tables as $table) {
        $tableExistsQuery = "SHOW TABLES LIKE '$table'";
        $tableExists = $db->executeS($tableExistsQuery);

        if ($tableExists) {
            $createTableQuery = "SHOW CREATE TABLE `$table`";
            $result = $db->executeS($createTableQuery);

            if ($result && isset($result[0]['Create Table'])) {
                fwrite($dumpFile, $result[0]['Create Table'] . ";\n\n");

                $rows = $db->executeS("SELECT * FROM `$table`");
                foreach ($rows as $row) {
                    if (isset($row['id_configuration'])) {
                        $row['id_configuration'] = $row['id_configuration'] + 100000;
                    }

                    $values = array_map('pSQL', array_values($row));
                    $values = "'" . implode("', '", $values) . "'";
                    $rowContent = "INSERT INTO `$table` VALUES ($values);\n";
                    fwrite($dumpFile, $rowContent);
                }

                fwrite($dumpFile, "\n\n");
            } else {
                echo "Error retrieving table structure for $table.<br>";
            }
        }
    }

    $langMapping = getLanguageMapping();

    $configurations = $db->executeS("SELECT * FROM `" . _DB_PREFIX_ . "configuration` WHERE `name` LIKE '$prefix%'");
    if ($configurations) {
        foreach ($configurations as $config) {
            $oldId = $config['id_configuration'];
            $newId = $oldId + 100000;

            $config['id_configuration'] = $newId;
            $values = array_map('pSQL', array_values($config));
            $values = "'" . implode("', '", $values) . "'";
            fwrite($dumpFile, "INSERT INTO `" . _DB_PREFIX_ . "configuration` VALUES ($values);\n");

            $configLangs = $db->executeS("SELECT * FROM `" . _DB_PREFIX_ . "configuration_lang` WHERE `id_configuration` = $oldId");
            if ($configLangs) {
                foreach ($configLangs as $configLang) {
                    $configLang['id_configuration'] = $newId;

                    // Map id_lang to target shop's language IDs
                    $isoCode = $db->getValue("SELECT `iso_code` FROM `" . _DB_PREFIX_ . "lang` WHERE `id_lang` = " . (int)$configLang['id_lang']);
                    if (isset($langMapping[$isoCode])) {
                        $configLang['id_lang'] = $langMapping[$isoCode];
                    } else {
                        // Skip insertion if the language does not exist in the target shop
                        continue;
                    }

                    $values = array_map('pSQL', array_values($configLang));
                    $values = "'" . implode("', '", $values) . "'";
                    fwrite($dumpFile, "INSERT INTO `" . _DB_PREFIX_ . "configuration_lang` VALUES ($values);\n");
                }
            }
        }
        fwrite($dumpFile, "\n\n");
    }

    fclose($dumpFile);

    return $dumpFilename; // Return the filename for later use
}

function getModulePrefix($moduleName)
{
    // Include the main module file
    require_once(_PS_MODULE_DIR_ . $moduleName . '/' . $moduleName . '.php');
    $moduleClass = ucfirst($moduleName);
    $module = new $moduleClass();

    // Generate the prefix
    return $module->setOptionsPrefix()->options_prefix;
}
