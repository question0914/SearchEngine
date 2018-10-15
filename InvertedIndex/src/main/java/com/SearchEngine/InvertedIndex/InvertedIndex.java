package com.SearchEngine.InvertedIndex;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;

/**
 * Created by zijianli on 10/11/17.
 */
public class InvertedIndex {
    /**
     * Switch the second output to text(String) to store the docID
     */
    public static class InvertedIndexMapper
            extends Mapper<Object, Text, Text, Text>{

        //private final static IntWritable one = new IntWritable(1);
        private Text word = new Text();

        public void map(Object key, Text value, Context context
        ) throws IOException, InterruptedException {
            StringTokenizer itr = new StringTokenizer(value.toString());
            Text docID = new Text();
            docID.set(itr.nextToken());
            while (itr.hasMoreTokens()) {
                word.set(itr.nextToken());
                context.write(word, docID);
            }
        }
    }

    /**
     * Switch the second output to Text to Store the output Strings containing docIDs and corresponding counts
     */
    public static class InvertedIndexReducer
            extends Reducer<Text,Text,Text,Text> {
        private Text result = new Text();

        public void reduce(Text key, Iterable<Text> docIDs,
                           Context context
        ) throws IOException, InterruptedException {
            //int sum = 0;
            /**
             * Create a HashMap to store the appearing docIDs and counts for all the words
             */
            Map<String, Integer> map = new HashMap<String, Integer>();
            for (Text docID : docIDs) {
                String k = docID.toString();
                map.put(k, map.getOrDefault(k, 0) + 1);
                //sum += val.get();
            }
            String s = "";
            for (Map.Entry<String, Integer> entry : map.entrySet())
                s = s + entry.getKey() + ": " + entry.getValue() + "\t";
            result.set(s);
            context.write(key, result);

        }
    }

        public static void main(String[] args) throws Exception {
            Configuration conf = new Configuration();
            Job job = Job.getInstance(conf, "Inverted Index");
            job.setJarByClass(InvertedIndex.class);
            job.setMapperClass(InvertedIndexMapper.class);
            job.setReducerClass(InvertedIndexReducer.class);
            job.setOutputKeyClass(Text.class);
            job.setOutputValueClass(Text.class);
            FileInputFormat.addInputPath(job, new Path(args[0]));
            FileOutputFormat.setOutputPath(job, new Path(args[1]));
            System.exit(job.waitForCompletion(true) ? 0 : 1);
        }
}
