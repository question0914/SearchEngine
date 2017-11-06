import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.io.*;
import java.util.HashMap;
import java.util.HashSet;
/**
 * Created by zijianli on 11/5/17.
 */
public class ExtractLinkes {
    public static void main(String args[]) throws IOException{
        String inFile = "src/main/resources/NYD Map.csv";
        String htmlPath = "/Users/zijianli/Downloads/NYD/NYD";
        HashMap<String, String> fileUrlMap = new HashMap<String, String>();
        HashMap<String, String> urlFileMap = new HashMap<String, String>();

        //Construct two hashMaps between file name and url
        try {
            BufferedReader br = new BufferedReader(new FileReader(inFile));
            String line;
            while ((line = br.readLine()) != null) {
                fileUrlMap.put(line.split(",")[0], line.split(",")[1]);
                urlFileMap.put(line.split(",")[1], line.split(",")[0]);
            }
        }catch(IOException e){
            e.printStackTrace();
        }
        File dir = new File(htmlPath);
        HashSet<String> edges = new HashSet<String>();

        //Parse and write edges into file
        int count = 0;
        for(File file: dir.listFiles()){
            if(!(file.getName().contains("html")))
                continue;
            count++;
            Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));
            Elements links = doc.select("a[href]");


            for(Element link : links){
                String url = link.attr("abs:href").trim();
                if(urlFileMap.containsKey(url)){
                    edges.add(file.getName() + " " + urlFileMap.get(url));
                }
            }
        }
        try{
            BufferedWriter bw = new BufferedWriter(new FileWriter("edgeList.txt"));
            for(String s: edges){
                bw.write(s + "\n");
            }
            bw.close();
        }catch(IOException e){
            e.printStackTrace();
        }
        System.out.print(count);
    }
}
