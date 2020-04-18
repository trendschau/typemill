<?php 

namespace Typemill;

class Settings
{	
	public static function loadSettings()
	{
		$defaultSettings 	= self::getDefaultSettings();
		$userSettings 		= self::getUserSettings();		
		
		$settings 			= $defaultSettings;

		if($userSettings)
		{
			$settings 			= array_merge($defaultSettings, $userSettings);
		}

		# no individual image sizes are allowed sind 1.3.4
		$settings['images']	= $defaultSettings['images'];

		# if there is no theme set
		if(!isset($settings['theme']))
		{
			# scan theme folder and get the first theme
			$themefolder = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR;
			$themes = array_diff(scandir($themefolder), array('..', '.'));
			$firsttheme = reset($themes);

			# if there is a theme with valid theme settings-file
			if($firsttheme && self::getObjectSettings('themes', $firsttheme))
			{
				$settings['theme'] = $firsttheme;
			}
			else
			{
				die('There is no theme in the theme-folder. Please add a theme from https://themes.typemill.net');
			}
		}

	    # i18n
	    # load the strings of the set language
	    $language = $settings['language'];
	    $settings['labels'] = self::getLanguageLabels($language);

		# We know the used theme now so create the theme path 
		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];

		# if there are no theme settings yet (e.g. no setup yet) use default theme settings
		if(!isset($settings['themes']))
		{
			$themeSettings = self::getObjectSettings('themes', $settings['theme']);
			$settings['themes'][$settings['theme']] = isset($themeSettings['settings']) ? $themeSettings['settings'] : false;
		}

		return array('settings' => $settings);
	}
	
	public static function getDefaultSettings()
	{
		$rootPath = __DIR__ . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR;
		
		return [
			'determineRouteBeforeAppMiddleware' 	=> true,
			'displayErrorDetails' 					=> false,
			'title'									=> 'TYPEMILL',
			'author'								=> 'Unknown',
			'copyright'								=> 'Copyright',
			'language'								=> 'en',
			'startpage'								=> true,
			'rootPath'								=> $rootPath,
			'themeFolder'							=> 'themes',
			'themeBasePath'							=> $rootPath,
			'themePath'								=> '',
			'settingsPath'							=> $rootPath . 'settings',
			'userPath'								=> $rootPath . 'settings' . DIRECTORY_SEPARATOR . 'users',
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'editor'								=> 'visual',
			'formats'								=> ['markdown', 'headline', 'ulist', 'olist', 'table', 'quote', 'image', 'video', 'file', 'toc', 'hr', 'definition', 'code'],
			'contentFolder'							=> 'content',
			'cache'									=> true,
			'cachePath'								=> $rootPath . 'cache',
			'version'								=> '1.3.4',
			'setup'									=> true,
			'welcome'								=> true,
			'images'								=> ['live' => ['width' => 820], 'thumbs' => ['width' => 250, 'height' => 150]],
		];
	}
	
	public static function getUserSettings()
	{
		$yaml = new Models\WriteYaml();
		
		$userSettings = $yaml->getYaml('settings', 'settings.yaml');
		
		return $userSettings;
	}


    # i18n
 	public static function getLanguageLabels($language)
	{
    	# if not present, set the English language
    	if( empty($language) )
    	{
      		$language = 'en';
    	}

    	# load the strings of the set language
		$yaml = new Models\WriteYaml();
		$labels = $yaml->getYaml('settings/languages', $language.'.yaml');
		
		return $labels;
	}

  public static function getVuejsLabels($language)
	{
    if( empty($language) ){
      $language = 'en';
    }
    
    // load the strings of the set language
		$yaml = new Models\WriteYaml();
    $labels = $yaml->getYaml('settings/languages', 'vuejs-'.$language.'.yaml');
		
		return $labels;
	}


	public static function getObjectSettings($objectType, $objectName)
	{
		$yaml = new Models\WriteYaml();
		
		$objectFolder 	= $objectType . DIRECTORY_SEPARATOR . $objectName;
		$objectFile		= $objectName . '.yaml';
		$objectSettings = $yaml->getYaml($objectFolder, $objectFile);

		return $objectSettings;
	}

	public static function createSettings()
	{
		$yaml = new Models\WriteYaml();
		
		# create initial settings file with only setup false
		if($yaml->updateYaml('settings', 'settings.yaml', array('setup' => false)))
		{
			return true; 
		}
		return false;
	}

	public static function updateSettings($settings)
	{
		# only allow if usersettings already exists (setup has been done)
		$userSettings 	= self::getUserSettings();
		
		if($userSettings)
		{
			# whitelist settings that can be stored in usersettings (values are not relevant here, only keys)
			$allowedUserSettings = ['displayErrorDetails' => true,
									'title' => true,
									'copyright' => true,
									'language' => true,
									'startpage' => true,
									'author' => true,
									'year' => true,
									'theme' => true,
									'editor' => true,
									'formats' => true,
									'setup' => true,
									'welcome' => true,
									'images' => true,
									'plugins' => true,
									'themes' => true,
									'latestVersion' => true,
									'logo' => true,
									'favicon' => true, 
								];

			# cleanup the existing usersettings
			$userSettings = array_intersect_key($userSettings, $allowedUserSettings);

			# cleanup the new settings passed as an argument
			$settings 	= array_intersect_key($settings, $allowedUserSettings);
			
			# merge usersettings with new settings
			$settings 	= array_merge($userSettings, $settings);

			# write settings to yaml
			$yaml = new Models\WriteYaml();
			$yaml->updateYaml('settings', 'settings.yaml', $settings);					
		}
	}
}
