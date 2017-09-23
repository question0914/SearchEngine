package com.searchEngine.webCrawler.controller;

import edu.uci.ics.crawler4j.crawler.CrawlConfig;
import edu.uci.ics.crawler4j.crawler.CrawlController;
import edu.uci.ics.crawler4j.fetcher.PageFetcher;
import edu.uci.ics.crawler4j.robotstxt.RobotstxtConfig;
import edu.uci.ics.crawler4j.robotstxt.RobotstxtServer;

import java.io.BufferedWriter;
import java.io.FileWriter;
import java.io.IOException;

/**
 * Created by zijianli on 9/20/17.
 */
public class Controller {
    public final static String targetSite = "www.nydailynews.com/";
    public static void main(String[] args) throws Exception {
        String crawlStorageFolder = "data/crawl/";
        String fetchFile = "fetch_nydailynews.csv";
        String visitFile = "visit_nydailynews.csv";
        String urlsFile = "urls_nydailynews.csv";


        /*Basic configuration of the crawler*/
        int numberOfCrawlers = 7;
        int maxPagesToFetch = 20000;
        int maxDepthOfCrawling = 16;
        int politeDelay = 1000;

        CrawlConfig config = new CrawlConfig();
        config.setCrawlStorageFolder(crawlStorageFolder);
        config.setMaxPagesToFetch(maxPagesToFetch);
        config.setMaxDepthOfCrawling(maxDepthOfCrawling);
        //config.setPolitenessDelay(politeDelay);
        config.setIncludeHttpsPages(true);
        config.setFollowRedirects(true);
        config.setIncludeBinaryContentInCrawling(true);



 /* Instantiate the controller for this crawl.*/
        PageFetcher pageFetcher = new PageFetcher(config);
        RobotstxtConfig robotstxtConfig = new RobotstxtConfig();
        //robotstxtConfig.setEnabled(false);
        RobotstxtServer robotstxtServer = new RobotstxtServer(robotstxtConfig, pageFetcher);
        CrawlController controller = new CrawlController(config, pageFetcher, robotstxtServer);
/*Create storage file*/
        try{
            BufferedWriter bw = new BufferedWriter(new FileWriter(crawlStorageFolder+fetchFile));
            bw.write("URL, Status Code\n");
            bw.close();

            bw = new BufferedWriter(new FileWriter(crawlStorageFolder+visitFile));
            bw.write("URLs Downloaded, size, # of outlinks found, content-type\n");
            bw.close();

            bw = new BufferedWriter(new FileWriter(crawlStorageFolder+urlsFile));
            bw.write("encountered URL, indicator\n");
            bw.close();
        }catch (IOException e){
            e.printStackTrace();
        }


 /* For each crawl, you need to add some seed urls. These are the first
 * URLs that are fetched and then the crawler starts following links
 * which are found in these pages */
        controller.addSeed("http://"+targetSite);
 /* Start the crawl. This is a blocking operation, meaning that your code
 * will reach the line after this only when crawling is finished. */
        controller.start(MyCrawler.class, numberOfCrawlers);
        System.out.println("Fethced attemps: "+MyCrawler.count);
    }
}
