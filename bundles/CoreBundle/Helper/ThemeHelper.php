<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception as MauticException;
use Mautic\CoreBundle\Templating\Helper\ThemeHelper as TemplatingThemeHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReference;
use Symfony\Component\Translation\TranslatorInterface;

class ThemeHelper
{
    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array|mixed
     */
    private $themes = [];

    /**
     * @var array
     */
    private $themesInfo = [];

    /**
     * @var array
     */
    private $steps = [];

    /**
     * @var string
     */
    private $defaultTheme;

    /**
     * @var TemplatingThemeHelper[]
     */
    private $themeHelpers = [];

    /**
     * Default themes which cannot be deleted.
     *
     * @var array
     */
    protected $defaultThemes = [
        'aurora',
        'blank',
        'cards',
        'fresh-center',
        'fresh-fixed',
        'fresh-left',
        'fresh-wide',
        'goldstar',
        'neopolitan',
        'oxygen',
        'skyline',
        'sparse',
        'sunday',
        'system',
        'vibrant',
    ];

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * ThemeHelper constructor.
     */
    public function __construct(PathsHelper $pathsHelper, TemplatingHelper $templatingHelper, TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper)
    {
        $this->pathsHelper          = $pathsHelper;
        $this->templatingHelper     = $templatingHelper;
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Get theme names which are stock Mautic.
     *
     * @return array
     */
    public function getDefaultThemes()
    {
        return $this->defaultThemes;
    }

    /**
     * @param string $defaultTheme
     */
    public function setDefaultTheme($defaultTheme)
    {
        $this->defaultTheme = $defaultTheme;
    }

    /**
     * @param $themeName
     *
     * @return TemplatingThemeHelper
     *
     * @throws MauticException\BadConfigurationException
     * @throws MauticException\FileNotFoundException
     */
    public function createThemeHelper($themeName)
    {
        if ('current' === $themeName) {
            $themeName = $this->defaultTheme;
        }

        return new TemplatingThemeHelper($this->pathsHelper, $themeName);
    }

    /**
     * @param $newName
     *
     * @return string
     */
    private function getDirectoryName($newName)
    {
        return InputHelper::filename($newName);
    }

    /**
     * @param $theme
     *
     * @return bool
     */
    public function exists($theme)
    {
        $root    = $this->pathsHelper->getSystemPath('themes', true).'/';
        $dirName = $this->getDirectoryName($theme);
        $fs      = new Filesystem();

        return $fs->exists($root.$dirName);
    }

    /**
     * @param $theme
     * @param $newName
     *
     * @throws MauticException\FileExistsException
     * @throws MauticException\FileNotFoundException
     */
    public function copy($theme, $newName)
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new MauticException\FileNotFoundException($theme.' not found!');
        }

        $dirName = $this->getDirectoryName($newName);

        $fs = new Filesystem();

        if ($fs->exists($root.$dirName)) {
            throw new MauticException\FileExistsException("$dirName already exists");
        }

        $fs->mirror($root.$theme, $root.$dirName);

        $this->updateConfig($root.$dirName, $newName);
    }

    /**
     * @param $theme
     * @param $newName
     *
     * @throws MauticException\FileNotFoundException
     * @throws MauticException\FileExistsException
     */
    public function rename($theme, $newName)
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new MauticException\FileNotFoundException($theme.' not found!');
        }

        $dirName = $this->getDirectoryName($newName);

        $fs = new Filesystem();

        if ($fs->exists($root.$dirName)) {
            throw new MauticException\FileExistsException("$dirName already exists");
        }

        $fs->rename($root.$theme, $root.$dirName);

        $this->updateConfig($root.$theme, $dirName);
    }

    /**
     * @param $theme
     *
     * @throws MauticException\FileNotFoundException
     */
    public function delete($theme)
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new MauticException\FileNotFoundException($theme.' not found!');
        }

        $fs = new Filesystem();
        $fs->remove($root.$theme);
    }

    /**
     * Updates the theme configuration and converts
     * it to json if still using php array.
     *
     * @param $themePath
     * @param $newName
     */
    private function updateConfig($themePath, $newName)
    {
        if (file_exists($themePath.'/config.json')) {
            $config = json_decode(file_get_contents($themePath.'/config.json'), true);
        }

        $config['name'] = $newName;

        file_put_contents($themePath.'/config.json', json_encode($config));
    }

    /**
     * Fetches the optional settings from the defined steps.
     *
     * @return array
     */
    public function getOptionalSettings()
    {
        $minors = [];

        foreach ($this->steps as $step) {
            foreach ($step->checkOptionalSettings() as $minor) {
                $minors[] = $minor;
            }
        }

        return $minors;
    }

    /**
     * @param string $template
     *
     * @return string The logical name for the template
     */
    public function checkForTwigTemplate($template)
    {
        $parser     = $this->templatingHelper->getTemplateNameParser();
        $templating = $this->templatingHelper->getTemplating();

        $template = $parser->parse($template);

        $twigTemplate = clone $template;
        $twigTemplate->set('engine', 'twig');

        // Does a twig version exist?
        if ($templating->exists($twigTemplate)) {
            return $twigTemplate->getLogicalName();
        }

        // Does a PHP version exist?
        if ($templating->exists($template)) {
            return $template->getLogicalName();
        }

        // Try any theme as a fall back starting with default
        $this->findThemeWithTemplate($templating, $twigTemplate);

        return $twigTemplate->getLogicalName();
    }

    /**
     * @param string $specificFeature
     * @param bool   $extended        returns extended information about the themes
     * @param bool   $ignoreCache     true to get the fresh info
     * @param bool   $includeDirs     true to get the theme dir details
     *
     * @return mixed
     */
    public function getInstalledThemes($specificFeature = 'all', $extended = false, $ignoreCache = false, $includeDirs = true)
    {
        if (empty($this->themes[$specificFeature]) || $ignoreCache) {
            $dir    = $this->pathsHelper->getSystemPath('themes', true);
            $finder = new Finder();
            $finder->directories()->depth('0')->ignoreDotFiles(true)->in($dir);

            $this->themes[$specificFeature]     = [];
            $this->themesInfo[$specificFeature] = [];
            foreach ($finder as $theme) {
                if (!file_exists($theme->getRealPath().'/config.json')) {
                    continue;
                }

                $config = json_decode(file_get_contents($theme->getRealPath().'/config.json'), true);

                if ('all' === $specificFeature || (isset($config['features']) && in_array($specificFeature, $config['features']))) {
                    $this->themes[$specificFeature][$theme->getBasename()]               = $config['name'];
                    $this->themesInfo[$specificFeature][$theme->getBasename()]           = [];
                    $this->themesInfo[$specificFeature][$theme->getBasename()]['name']   = $config['name'];
                    $this->themesInfo[$specificFeature][$theme->getBasename()]['key']    = $theme->getBasename();
                    $this->themesInfo[$specificFeature][$theme->getBasename()]['config'] = $config;

                    if ($includeDirs) {
                        $this->themesInfo[$specificFeature][$theme->getBasename()]['dir']            = $theme->getRealPath();
                        $this->themesInfo[$specificFeature][$theme->getBasename()]['themesLocalDir'] = $this->pathsHelper->getSystemPath(
                            'themes',
                            false
                        );
                    }
                }
            }
        }

        if ($extended) {
            return $this->themesInfo[$specificFeature];
        }

        return $this->themes[$specificFeature];
    }

    /**
     * @param string $theme
     * @param bool   $throwException
     *
     * @return TemplatingThemeHelper
     *
     * @throws MauticException\FileNotFoundException
     * @throws MauticException\BadConfigurationException
     */
    public function getTheme($theme = 'current', $throwException = false)
    {
        if (empty($this->themeHelpers[$theme])) {
            try {
                $this->themeHelpers[$theme] = $this->createThemeHelper($theme);
            } catch (MauticException\FileNotFoundException $e) {
                if (!$throwException) {
                    // theme wasn't found so just use the first available
                    $themes = $this->getInstalledThemes();

                    foreach ($themes as $installedTheme => $name) {
                        try {
                            if (isset($this->themeHelpers[$installedTheme])) {
                                // theme found so return it
                                return $this->themeHelpers[$installedTheme];
                            } else {
                                $this->themeHelpers[$installedTheme] = $this->createThemeHelper($installedTheme);
                                // found so use this theme
                                $theme = $installedTheme;
                                $found = true;
                                break;
                            }
                        } catch (MauticException\FileNotFoundException $e) {
                            continue;
                        }
                    }
                }

                if (empty($found)) {
                    // if we get to this point then no template was found so throw an exception regardless
                    throw $e;
                }
            }
        }

        return $this->themeHelpers[$theme];
    }

    /**
     * Install a theme from a zip package.
     *
     * @param string $zipFile path
     *
     * @return bool
     *
     * @throws MauticException\FileNotFoundException
     * @throws \Exception
     */
    public function install($zipFile)
    {
        if (false === file_exists($zipFile)) {
            throw new MauticException\FileNotFoundException();
        }

        if (false === class_exists('ZipArchive')) {
            throw new \Exception('mautic.core.ziparchive.not.installed');
        }

        $themeName = basename($zipFile, '.zip');

        if (in_array($themeName, $this->getDefaultThemes())) {
            throw new \Exception($this->translator->trans('mautic.core.theme.default.cannot.overwrite', ['%name%' => $themeName], 'validators'));
        }

        $themePath = $this->pathsHelper->getSystemPath('themes', true).'/'.$themeName;
        $zipper    = new \ZipArchive();
        $archive   = $zipper->open($zipFile);

        if (true !== $archive) {
            throw new \Exception($this->getExtractError($archive));
        }

        $requiredFiles      = ['config.json', 'html/message.html.twig'];
        $foundRequiredFiles = [];
        $allowedFiles       = [];
        $allowedExtensions  = $this->coreParametersHelper->get('theme_import_allowed_extensions');

        $config = [];
        for ($i = 0; $i < $zipper->numFiles; ++$i) {
            $entry = $zipper->getNameIndex($i);
            if (0 === strpos($entry, '/')) {
                $entry = substr($entry, 1);
            }

            $extension = pathinfo($entry, PATHINFO_EXTENSION);

            // Check for required files
            if (in_array($entry, $requiredFiles)) {
                $foundRequiredFiles[] = $entry;
            }

            // Filter out dangerous files like .php
            if (empty($extension) || in_array(strtolower($extension), $allowedExtensions)) {
                $allowedFiles[] = $entry;
            }

            if ('config.json' === $entry) {
                $config = json_decode($zipper->getFromName($entry), true);
            }
        }

        if (!empty($config['features'])) {
            foreach ($config['features'] as $feature) {
                $featureFile     = sprintf('html/%s.html.twig', strtolower($feature));
                $requiredFiles[] = $featureFile;

                if (in_array($featureFile, $allowedFiles)) {
                    $foundRequiredFiles[] = $featureFile;
                }
            }
        }

        if ($missingFiles = array_diff($requiredFiles, $foundRequiredFiles)) {
            throw new MauticException\FileNotFoundException($this->translator->trans('mautic.core.theme.missing.files', ['%files%' => implode(', ', $missingFiles)], 'validators'));
        }

        // Extract the archive file now
        if (!$zipper->extractTo($themePath, $allowedFiles)) {
            throw new \Exception('mautic.core.update.error_extracting_package');
        } else {
            $zipper->close();
            unlink($zipFile);

            return true;
        }
    }

    /**
     * Get the error message from the zip archive.
     *
     * @param \ZipArchive $archive
     *
     * @return string
     */
    public function getExtractError($archive)
    {
        switch ($archive) {
            case \ZipArchive::ER_EXISTS:
                $error = 'mautic.core.update.archive_file_exists';
                break;
            case \ZipArchive::ER_INCONS:
            case \ZipArchive::ER_INVAL:
            case \ZipArchive::ER_MEMORY:
                $error = 'mautic.core.update.archive_zip_corrupt';
                break;
            case \ZipArchive::ER_NOENT:
                $error = 'mautic.core.update.archive_no_such_file';
                break;
            case \ZipArchive::ER_NOZIP:
                $error = 'mautic.core.update.archive_not_valid_zip';
                break;
            case \ZipArchive::ER_READ:
            case \ZipArchive::ER_SEEK:
            case \ZipArchive::ER_OPEN:
            default:
                $error = 'mautic.core.update.archive_could_not_open';
                break;
        }

        return $error;
    }

    /**
     * Creates a zip file from a theme and returns the path where it's stored.
     *
     * @param string $themeName
     *
     * @return string
     *
     * @throws Exception
     */
    public function zip($themeName)
    {
        $themePath = $this->pathsHelper->getSystemPath('themes', true).'/'.$themeName;
        $tmpPath   = $this->pathsHelper->getSystemPath('cache', true).'/tmp_'.$themeName.'.zip';
        $zipper    = new \ZipArchive();
        $finder    = new Finder();

        if (file_exists($tmpPath)) {
            @unlink($tmpPath);
        }

        $archive = $zipper->open($tmpPath, \ZipArchive::CREATE);

        $finder->files()->in($themePath);

        if (true !== $archive) {
            throw new \Exception($this->getExtractError($archive));
        } else {
            foreach ($finder as $file) {
                $filePath  = $file->getRealPath();
                $localPath = $file->getRelativePathname();
                $zipper->addFile($filePath, $localPath);
            }
            $zipper->close();

            return $tmpPath;
        }

        return false;
    }

    /**
     * @throws MauticException\BadConfigurationException
     * @throws MauticException\FileNotFoundException
     */
    private function findThemeWithTemplate(EngineInterface $templating, TemplateReference $template)
    {
        preg_match('/^:(.*?):(.*?)$/', $template->getLogicalName(), $match);
        $requestedThemeName = $match[1];

        // Try the default theme first
        $defaultTheme = $this->getTheme();
        if ($requestedThemeName !== $defaultTheme->getTheme()) {
            $template->set('controller', $defaultTheme->getTheme());
            if ($templating->exists($template)) {
                return;
            }
        }

        // Find any theme as a fallback
        $themes = $this->getInstalledThemes('all', true);
        foreach ($themes as $theme) {
            // Already handled the default
            if ($theme['key'] === $defaultTheme->getTheme()) {
                continue;
            }

            // Theme name is stored in the controller parameter
            $template->set('controller', $theme['key']);

            if ($templating->exists($template)) {
                return;
            }
        }
    }
}
