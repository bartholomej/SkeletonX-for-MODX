<?php
/**
 * setupBabel (experimental)
 *
 * DESCRIPTION
 * Automatically set context settings for Babel, create gateway plugin and generate .htaccess code ;)
 *
 * USAGE
 * 1. Create as many context as you want ('Context Key' has to be lowercase ISO name of state, e.g. en, de, cs, ...)
 * 2. Run this snippet
 * 3. "Copy and paste" generated code to your .htaccess file.
 * 4. Install Babel Package
 * 5. Enjoy!
 *
 * AUTHOR
 * BART! http://github.com/bartholomej
 *
 */

//  Get all front contexts
    $site_url = $modx->getOption('site_url');
    $site_start = $modx->getOption('site_start');
    $ctx = $modx->getCollection('modContext', array('key:NOT IN' => array('mgr')));
    
    $contexts = array();    
    foreach ($ctx as $context) {
        $contexts[] = $context->get('key');
    }    

    if (count($contexts) <= 1) {
        echo "WARNING: You have only one context. Create more contexts first!";
        exit;
    }    
    
    // Building and saving context settings (not rewrite if already exist)
    function buildContextSettings($modx, $contexts, $site_url, $site_start) {
        $settings = array();
        foreach ($contexts as $context) {
            if ($context != 'web') { $postfix = $context . '/'; }
            $settings[] = array ('key' => 'base_url',   'value' => '/' . $postfix,         'context_key' => $context,);
            $settings[] = array ('key' => 'cultureKey', 'value' => $context,               'context_key' => $context,);
            $settings[] = array ('key' => 'site_start', 'value' => $site_start,            'context_key' => $context,);
            $settings[] = array ('key' => 'site_url',   'value' => $site_url . $postfix,   'context_key' => $context,);
        }
            
        foreach($settings as $val){    
            $setContext = $modx->newObject('modContextSetting');
            $setContext->fromArray($val, '', true);
            $setContext->set('xtype', 'textfield');
            $setContext->set('namespace', 'core');
            $setContext->set('area', 'language');
            
            if (!$setContext->save()) {            
                $errContextSettings[] = $val;
            }
        }

        if ($errContextSettings) {
            ob_start();
            var_dump($errContextSettings);
            $ctxSettingsOutput = '<h3>WARNING: These context settings already exist:</h3>' . ob_get_clean();            
        } else {
            $ctxSettingsOutput = '<h3>Babel settings successfully created for all contexts...</h3>';
        }
        return $ctxSettingsOutput;
    }        
    

    // Building gateway plugin
    function buildGatewayCode($contexts) {
        $gateway = '
            /* SkeletonX generated gateway plugin for babel: https://github.com/bartholomej/SkeletonX-for-MODX */
        if ($modx->context->get(\'key\') != "mgr") { 
            switch($_REQUEST[\'cultureKey\']) { 
        ';
            
        foreach ($contexts as $context) {
            if ($context != 'web') {
                $gateway .= '
                case \''.$context.'\':
                    $modx->switchContext(\''.$context.'\');
                    break;   
                ';
            }
        } 

        $gateway .= '
                default:
                    /* Set the default context here */
                    $modx->switchContext(\'web\');
                    break;
                    
            }
            /* unset GET var to avoid appending cultureKey=xy to URLs by other components */
            unset($_GET[\'cultureKey\']);
        }
        ';

        return $gateway;
    }

    // Building htaccess
    function buildHtaccessCode($contexts) {
        $contextKeys = implode("|", $contexts);

        $htaccess .= ('
            # redirect all requests to /XX/favicon.ico
            # to /favicon.ico
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^('. $contextKeys .')/favicon.ico$ favicon.ico [L,QSA]
               
            # redirect all requests to /XX/assets* to /assets*
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^('. $contextKeys .')/assets(.*)$ assets$2 [L,QSA]
              
            # redirect all other requests to /XX/* 
            # to index.php and set the cultureKey parameter
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^('. $contextKeys .')?/?(.*)$ index.php?cultureKey=$1&q=$2 [L,QSA]
        ');
        return $htaccess;
    }    

    function createGateway($modx, $contexts) {
        $plugin = $modx->newObject('modPlugin');
        $plugin->set('name', 'gateway');
        $plugin->set('description', 'SkeletonX generated gateway plugin for Babel: https://github.com/bartholomej/SkeletonX-for-MODX');
        $plugin->set('events', array('OnPageNotFound'));
        $plugin->setContent( buildGatewayCode($contexts) );

        $event = $modx->newObject('modPluginEvent');
        $event->set('event','OnHandleRequest');
        $event->set('priority',0);
        $event->set('propertyset',0);
        $events = array($event);
        $plugin->addMany($events);
        $modx->log(xPDO::LOG_LEVEL_INFO,'Packaged in '. count($events) .' Plugin Events.'); flush();
        unset($events);
        
        if ($plugin->save()) {
            $pluginOutput = '<h3>Plugin gateway was successfully created!</h3><code>' . nl2br(buildGatewayCode($contexts)) . '</code>';
        } else {
            $pluginOutput = '<h3>WARNING: Plugin "gateway" already exist!</h3>';
        }
        return $pluginOutput;
    }        

    // Step 1
    $output = '<h1>Setting up Babel and have a website with multiple languages</h1>
               <h2>Step #1 - Automatically generated</h2>';    
    $output .= buildContextSettings($modx, $contexts, $site_url, $site_start);
    $output .= createGateway($modx, $contexts); 

    // Step 2
    $output .= '<h2>Step #2 - Setting up .htaccess</h2>
                You have to manually add these lines into your .htaccess file <br />';
    $output .= '<textarea rows="25" cols="150">';
    $output .= buildHtaccessCode($contexts);
    $output .= '</textarea><br />';
    
    // Step 3
    $output .= '<h2>Step #3 - Installing Babel</h2>'; 
    $output .= 'Almost there! We now can install the Babel package from package manager.<br /><br />
                Now you should remove this snippet call!<br /><br />
                Based on this great manual: http://designfromwithin.com/blog-webdesign-development/2012/01/12/modx-multilingual-setting-up-babel-and-have-a-website-with-multible-languages/                  
                      ';                          

    // Clear context cache // TODO
    $cacheRefreshOptions =  array( 'context_settings' => array('contexts' => array($contexts) ));
    $modx->cacheManager-> refresh($cacheRefreshOptions);

    return $output;