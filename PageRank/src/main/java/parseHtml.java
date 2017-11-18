import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.AutoDetectParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;

import java.io.*;
import java.util.ArrayList;
import java.util.List;


/**
 * Created by zijianli on 11/17/17.
 */
public class parseHtml {
    public static void main(String args[]) throws IOException, SAXException, TikaException {
        String htmlPath = "/Users/zijianli/Downloads/NYD/NYD";
        File dir = new File(htmlPath);
        int count = 0;
        List<String> log = new ArrayList<String>();
        for(File file: dir.listFiles()){
            if(!(file.getName().contains("html")))
                continue;
            count++;
            System.out.println(count);
            BodyContentHandler handler = new BodyContentHandler(-1);
            AutoDetectParser parser = new AutoDetectParser();
            Metadata metadata = new Metadata();
            InputStream stream = new FileInputStream(file);
            parser.parse(stream, handler, metadata);
            log.add(handler.toString());
        }

        try{
            BufferedWriter bw = new BufferedWriter(new FileWriter("big.txt"));
            for(String s: log){
                bw.write(s);
            }
            bw.close();
        }catch(IOException e){
            e.printStackTrace();
        }
        System.out.print(count);
    }
}
