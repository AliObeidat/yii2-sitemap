<?php


namespace aliobeidat\sitemap;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class SitemapGenerator
 * The class is designed to create a sitemap
 *
 * Usage example:
 * ```
 *  use aliobeidat/sitemap/SitemapGenerator;
 *
 *	Yii::$app->urlManager->baseUrl = 'http://site.com'; // base url use in sitemap urls creation
 *
 *	$sitemap = new ArticlesSitemap(); // must implement a SitemapInterface
 *	$sitemapGenerator = new SitemapGenerator([
 *	 	'sitemaps' => [$sitemap],
 *	 	'dir' => '@webRoot',
 *	]);
 *	$sitemapGenerator->generate();
 * ```
 *
 * @package common\components
 */
class SitemapGenerator extends Component
{
    /**
     * @var string directory for writing sitemap files. The use of aliases is allowed.
     */
    public $dir = '';

    /**
     * @var string base directory for returning sitemap file url from it
     */
    public $baseUrlDir = '';

    /**
     * @var string name of the sitemap index file
     */
    public $indexFilename = 'sitemap.xml';

    /**
     * @var string record format of the last page change
     */
    public $lastmodFormat = 'Y-m-d';

    /**
     * @var SitemapInterface[] set of sitemap objects
     */
    public $sitemaps = [];

    /**
     * @var array set all languages that you want to create sitemap for it
     * the default language will be en
     */
    public $languages = ['en'];

    /**
     * @var int the maximum number of addresses in one card.
     * If the number of addresses in the sitemap is more than the specified value,
     * then the sitemap will be split into several sitemaps in this way,
     * so that each has no more addresses than the specified value.
     * If it is "0", then the cards will not be split into several and one card can
     * contain an unlimited number of addresses.
     */
    public $maxUrlsCount = 45000;

    /**
     * @var array stores information about generated sitemaps
     */
    protected $createdSitemaps = [];

    /**
     * Creates sitemaps
     */
    public function generate()
    {
        foreach ($this->languages as $language){
            foreach ($this->sitemaps as $sitemap) {
                $this->createSitemap($sitemap, $language);
            }
        }

        $this->createIndexSitemap();
        $this->cleanOldFiles();
    }

    /**
     * Creates an index sitemap
     *
     * @return string
     */
    protected function createIndexSitemap()
    {
        $sitemapIndex = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $sitemapIndex .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $baseUrl = Yii::$app->urlManager->baseUrl;

        $sitemaps = $this->createdSitemaps;
        self::sortByLastmod($sitemaps);

        if (!empty($this->baseUrlDir)){
            $baseUrl .="/{$this->baseUrlDir}";
        }

        foreach ($sitemaps as $sitemap) {
            $sitemapIndex .= '    <sitemap>' . PHP_EOL;
            $sitemapIndex .= "        <loc>$baseUrl/$sitemap[loc]</loc>" . PHP_EOL;

            if (!empty($sitemap['lastmodTimestamp'])) {
                $lastmod = date($this->lastmodFormat, $sitemap['lastmodTimestamp']);
                $sitemapIndex .= "        <lastmod>$lastmod</lastmod>" . PHP_EOL;
            }

            $sitemapIndex .= '    </sitemap>' . PHP_EOL;
        }

        $sitemapIndex .= '</sitemapindex>';
        $this->createSitemapFile($this->indexFilename, $sitemapIndex);

        $this->createdSitemaps[] = [
            'loc'              => $this->indexFilename,
            'lastmodTimestamp' => date($this->lastmodFormat),
        ];

        return $sitemapIndex;
    }

    /**
     * Creates a sitemap from a $sitemap object and writes information
     * about the created sitemap to the $this->createdSitemaps array
     *
     * @param SitemapInterface $sitemap
     * @param string $language the language that you want to create site map for it
     *
     * @return boolean
     */
    protected function createSitemap(SitemapInterface $sitemap, $language)
    {
        if (!$urlsQueries = $sitemap->getUrlsQueries($this->maxUrlsCount)) {
            return false;
        }

        // set current language as required language
        Yii::$app->language = $language;
        $multipleSitemapFlag = count($urlsQueries) > 1;
        $i                   = 1;

        foreach ($urlsQueries as $urlsQuery) {
            if (!$urlsData = $sitemap->getUrls($urlsQuery)) {
                continue;
            }

            self::sortByLastmod($urlsData);
            $freshTimestamp = 0;
            $urlset         = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $urlset .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

            foreach ($urlsData as $url) {
                $urlset .= '    <url>' . PHP_EOL;
                $urlset .= "        <loc>$url[url]</loc>" . PHP_EOL;

                if (!empty($url['lastmodTimestamp'])) {
                    $date = date($this->lastmodFormat, $url['lastmodTimestamp']);
                    $urlset .= "        <lastmod>$date</lastmod>" . PHP_EOL;

                    if ($freshTimestamp < $url['lastmodTimestamp']) {
                        $freshTimestamp = $url['lastmodTimestamp'];
                    }
                }

                $urlset .= '    </url>' . PHP_EOL;
            }

            $urlset .= '</urlset>';
            $currentSitemapFilename = $multipleSitemapFlag ? "{$language}-{$sitemap->getName()}{$i}.xml" : "{$language}-{$sitemap->getName()}.xml";

            $this->createdSitemaps[] = [
                'loc'              => $currentSitemapFilename,
                'lastmodTimestamp' => $freshTimestamp,
            ];
            if (!$this->createSitemapFile($currentSitemapFilename, $urlset)) {
                return false;
            }
            $i++;
        }

        return true;
    }

    /**
     * Creates a sitemap file
     *
     * @param $filename
     * @param $data
     *
     * @return int
     */
    protected function createSitemapFile($filename, $data)
    {
        $directoryName = $this->getDirectoryName();
        $fullFilename =  $directoryName. '/' . $filename;

        return file_put_contents($fullFilename, $data);
    }

    /**
     * return sitemap base directory
     * @return bool|string
     */
    protected function getDirectoryName()
    {
        if (strpos('@', $this->dir) !== false){
            $directoryName = Yii::getAlias($this->dir);
        } else {
            $directoryName = $this->dir;
        }

        return $directoryName;
    }

    /**
     * Sorts urls by lastmod in descending order
     *
     * @param array $urls
     */
    protected static function sortByLastmod(array &$urls)
    {
        $lastmod = [];

        foreach ($urls as $key => $row) {
            $lastmod[$key] = !empty($row['lastmodTimestamp']) ? $row['lastmodTimestamp'] : 0;
        }

        array_multisort($lastmod, SORT_DESC, $urls);
    }

    /**
     * Clean old site map files that did not used any more
     * after we create new site map files
     */
    protected function cleanOldFiles()
    {
        $directoryName = $this->getDirectoryName();
        $sitemapFiles = scandir($directoryName);
        $createdSitemapsFiles = $this->createdSitemaps;

        if (!empty($sitemapFiles) && !empty($createdSitemapsFiles)){
            $sitemapFiles = array_flip($sitemapFiles);
            $createdSitemapsFiles = ArrayHelper::index($createdSitemapsFiles, 'loc');

            foreach ($sitemapFiles as $sitemapFile => $sitemapIndex){
                $sitemapFile = trim($sitemapFile, '.');
                if (!empty($sitemapFile) && !isset($createdSitemapsFiles[$sitemapFile])){
                    unlink($directoryName.'/'.$sitemapFile);
                }
            }
        }
    }
}