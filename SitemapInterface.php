<?php


namespace aliobeidat\sitemap;

/**
 * Interface SitemapInterface
 * Sitemap class interface.
 */
interface SitemapInterface
{
    /**
     * Returns the name of the sitemap (matches the file name without permission).
     *
     * For example: 'sitemap-articles', 'sitemap-news' etc.
     *
     * @return string
     */
    public function getName();

    /**
     * @param $urlsQuery database query to return urls
     * Returns a list of sitemap urls.
     * The 'lastmodTimestamp' key is optional.
     *
     * For example:
     * ```
     *  [
     *      ['url'=> 'http://site.com/1', 'lastmodTimestamp' => 12312312312],
     *      ['url'=> 'http://site.com/2', 'lastmodTimestamp' => 12312312342],
     *      ['url'=> 'http://site.com/3'],
     *  ]
     * ```
     *
     * @return array
     */
    public function getUrls($urlsQuery);

    /**
     * @param $maxUrlsCount integer max number of rows inside each query
     * Return a list of sql queries chunks that needs to run to get urls
     *
     * example:
     * ```
     * [
     * 'select * from table_name where id > 1 and id < 10',
     * 'select * from table_name where id > 10 and id < 20',
     * 'select * from table_name where id > 20 and id < 30'
     * ]
     * ```
     *
     * @return array
     */
    public function getUrlsQueries($maxUrlsCount);
}