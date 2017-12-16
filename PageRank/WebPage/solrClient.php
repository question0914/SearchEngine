<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');
include 'SpellCorrector.php';
include 'simple_html_dom.php';
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$map_file = fopen('NYD Map.csv', 'r');
$url_set = array();
while (($line = fgetcsv($map_file)) !== FALSE) {
    $url_set[$line[0]] = $line[1];
}
if ($query)
{
    // The Apache Solr Client library should be on the include path
    // which is usually most easily accomplished by placing in the
    // same directory as this script ( . or current directory is a default
    // php include path entry in the php.ini)
    require_once('solr-php-client/Apache/Solr/Service.php');
    // create a new solr service instance - host, port, and corename
    // path (all defaults in this example)
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
    // if magic quotes is enabled then stripslashes will be needed
    if (get_magic_quotes_gpc() == 1)
    {
        $query = stripslashes($query);
    }
    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted by searching (i.e. connection
    // problems or a query parsing error)
    try
    {
        $query = strtolower($query);
        $terms = preg_split('/\s+/',$query);
        $correct_terms = array();
        $spell_error = false;
        $flag =isset($_REQUEST['f']) ? true : false;
        if($flag == false){
            for($i=0;$i<sizeof($terms);++$i){
                $term = $terms[$i];
                $correct_term = strtolower(SpellCorrector::correct($term));
                if($term != $correct_term){
                    $spell_error = true;
                }
                array_push($correct_terms,$correct_term);
            }
            if($spell_error){
                $correct_terms = implode(" ",$correct_terms);
                $query = $correct_terms;
                $additionalPara = array('sort' => 'pageRankFile desc');
                $pr_results = $solr->search($correct_terms, 0, $limit, $additionalPara);
                $results = $solr->search($correct_terms, 0, $limit);
            }
            else{
                $additionalPara = array('sort' => 'pageRankFile desc');
                $pr_results = $solr->search($query, 0, $limit, $additionalPara);
                $results = $solr->search($query, 0, $limit);
            }
        }
    }
    catch (Exception $e)
    {
        // in production you'd probably log or email this error to an admin
        // and then show a special message to the user but for this example
        // we're going to show the full exception
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}
?>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>Solr vs PageRank Searching</title>
    <style>
        a:link {
            color: mediumblue;
            background-color: transparent;
            text-decoration: none;
        }

        a:visited {
            color: purple;
            background-color: transparent;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .point{
            font-size: 20px;
        }
        .snippet{
            font-weight:bold;
        }
        .italian{
            font-style: italic;
            font-size: 20px;
            /*color: #525D76;*/
        }
    </style>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>
<body>
<form class = "point" accept-charset="utf-8" method="get" >
    <label for="q">Search:</label>
    <input id="q"  name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" onkeyup="autoCompletion(this.value,event)" style="width: 500px; font-size: 20px;"/>
    <input id="go" type="submit" value="GO!" style="font-size: 20px">
</form>

<?php
if($query){
    if($spell_error){
        ?>
        <h2> Showing results for: <a href="solrClient.php?q=<?=$correct_terms?>"><?= $correct_terms; ?></a></h2>
        <?php

    }
}
?>
<table>
<?php
// display results
if ($results)
{
    $total = (int) $results->response->numFound;
    $start = min(1, $total);
    $end = min($limit, $total);

    $pr_total = (int) $pr_results->response->numFound;
    $pr_start = min(1, $pr_total);
    $pr_end = min($limit, $pr_total);
    ?>
    <table>
        <td width="50%" valign="top">
            <h2>Solr Lucene Results</h2>
            <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
            <ol>
                <?php
                // iterate result documents
                foreach ($results->response->docs as $doc)
                {
                    ?>
                    <li>
                        <div>
                            <?php
                            // iterate document fields / values
                            $title = "";
                            $id = "";
                            $url = "";
                            $description = "";
                            foreach ($doc as $field => $value)
                            {
                                if($field == "title"){
                                    $title = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($field == "id"){
                                    $id = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($field == "description"){
                                    $description = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($id != ""){
                                    $url = $url_set[str_replace("/Users/zijianli/Documents/solr-7.1.0/NYD/", "", $id)];
                                }
//                                if($field == "og_url"){
//                                    $url = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
//                                }
//                                if($url == "")
//                                    $url = "N/A";
                            }
                            $shown = str_replace("/Users/zijianli/Documents/solr-7.1.0/", "", $id);
                            echo "<a href = '{$shown}'><h2>".$title."</h2></a>";
                            echo "URL: <a href = '{$shown}'>".$url."</a></br></br>";
                            echo "Id: ".$id. "</br></br>";
                            echo "Description: ".$description."</br></br>";

                            $content = file_get_html(str_replace("/Users/zijianli/Documents/solr-7.1.0/", "", $id))->plaintext;
                            $sentences = preg_split('/(\.)/',$content);
                            //$sentences = preg_split('/(?<=[.?!])\s+(?=[a-z])/i',$content);
                            $words = preg_split('/\s+/', $query);
                            $snippet = "";
                            $all_query = "/";
                            $start_delim = "(?=.*?\b";
                            $end_delim = "\b)";
                            foreach($words as $item){
                                $all_query = $all_query.$start_delim.$item.$end_delim;
                            }
                            $all_query=$all_query."^.*$/i";
                            //Try match whole query
                            foreach($sentences as $sentence){
                                $check = array();
                                $check = implode(" ",$words);
                                $check = " ".$check." ";
                                if(stripos($sentence, $check)!=false){
                                    //echo $sentence;
                                    $index = stripos($content, $sentence);
                                    $start_index = max(0, ($index + stripos($sentence, $check) - 5));
                                    while($start_index > 0){
                                         if($content[$start_index] == ' ')
                                             break;
                                         $start_index = $start_index-1;
                                     }
                                     $end_index = min($start_index + 150, strlen($content)-1);
                                     while($end_index < strlen($content)){
                                         if($content[$end_index] == ' ')
                                             break;
                                         $end_index = $end_index+1;
                                     }
                                    $snippet = substr($content,$start_index,$end_index - $start_index);
                                    break;
                                }
                            }
                            if(strlen($snippet) <= 1){
                                foreach($sentences as $sentence){
                                if(preg_match($all_query, $sentence)>0){
                                    $index = stripos($content, $sentence);
                                    $start_index = max(0, ($index + stripos($sentence, $words[0]) - 5));
                                    while($start_index > 0){
                                         if($content[$start_index] == ' ')
                                             break;
                                         $start_index = $start_index-1;
                                     }
                                     $end_index = min($start_index + 150, strlen($content)-1);
                                     while($end_index < strlen($content)){
                                         if($content[$end_index] == ' ')
                                             break;
                                         $end_index = $end_index+1;
                                     }

                                    // $sen_length = strlen($sentence);
                                    // if($sen_length >= 160){
                                    //     $snippet = $sentence;
                                    //     break;
                                    // }
                                    // $rest = 160 - $sen_length;
                                    // $start_index = max(0,$index - (int)($rest/2));
                                    // while($start_index > 0){
                                    //     if($content[$start_index] == ' ')
                                    //         break;
                                    //     $start_index = $start_index-1;
                                    // }
                                    // $end_index = min($start_index + 155, strlen($content)-1);
                                    // while($end_index < strlen($content)){
                                    //     if($content[$end_index] == ' ')
                                    //         break;
                                    //     $end_index = $end_index+1;
                                    // }
                                    $snippet = substr($content,$start_index,$end_index - $start_index);
                                    break;
                                }
                            }
                        }
                            if(strlen($snippet) <= 1){
                                foreach($words as $word){
                                    $word = " ".$word." ";
                                    if(stripos($content, $word) != false) {
                                        $index = stripos($content, $word);
                                        $index += 1;//adjust for one whitespace
                                        $start_index = max(0,$index - 80);
                                        while($start_index != 0){
                                            if($content[$start_index] == ' ')
                                                break;
                                            $start_index = $start_index-1;
                                        }
                                        $end_index = min($start_index + 155, strlen($content)-1);
                                        while($end_index < strlen($content)){
                                            if($content[$end_index] == ' ')
                                                break;
                                            $end_index = $end_index+1;
                                        }
                                        $snippet = substr($content,$start_index,$end_index - $start_index);
                                        break;
                                    }
                                }
                            }

                            foreach($words as $word){
                                $snippet = preg_replace('/\b'.$word.'\b/i',"<span class='snippet'>\$0</span>",$snippet);
                            }
                            echo "<div class = italian>...".$snippet."...</div>";
                            ?>
                        </div>
                    </li>
                    <?php
                }
                ?>
            </ol>
        </td>
        <td width="50%" valign="top">
            <h2>Page Rank Results</h2>
            <div>Results <?php echo $pr_start; ?> - <?php echo $pr_end;?> of <?php echo $pr_total; ?>:</div>
            <ol>
                <?php
                // iterate result documents
                foreach ($pr_results->response->docs as $doc)
                {
                    ?>
                    <li>
                        <div>
                            <?php
                            // iterate document fields / values
                            $pr_title = "";
                            $pr_id = "";
                            $pr_url = "";
                            $pr_description = "";
                            foreach ($doc as $field => $value)
                            {
                                if($field == "title"){
                                    $pr_title = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($field == "id"){
                                    $pr_id = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($field == "description"){
                                    $pr_description = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
                                }
                                if($pr_id != ""){
                                    $pr_url = $url_set[str_replace("/Users/zijianli/Documents/solr-7.1.0/NYD/", "", $pr_id)];
                                }
                            }
                            $pr_shown = str_replace("/Users/zijianli/Documents/solr-7.1.0/", "", $pr_id);
                            echo "<a href = '{$pr_shown}'><h2>".$pr_title."</h2></a>";
                            echo "URL: <a href = '{$pr_shown}'>".$pr_url."</a></br></br>";
                            echo "Id: ".$pr_id. "</br></br>";
                            echo "Description: ".$pr_description."</br></br>";

                            $content = file_get_html(str_replace("/Users/zijianli/Documents/solr-7.1.0/", "", $pr_id))->plaintext;
                            //$sentences = preg_split('/(.|<br>)/',$content);
                            $sentences = preg_split('/(\.)/',$content);

                            //$words = explode(" ", $query);
                            $snippet = "";
                            //Try match whole query
                            foreach($sentences as $sentence){
                                $check = array();
                                $check = implode(" ",$words);
                                $check = " ".$check." ";
                                if(stripos($sentence, $check)!=false){
                                    //echo $sentence;
                                    $index = stripos($content, $sentence);
                                    $start_index = max(0, ($index + stripos($sentence, $check) - 5));
                                    while($start_index > 0){
                                         if($content[$start_index] == ' ')
                                             break;
                                         $start_index = $start_index-1;
                                     }
                                     $end_index = min($start_index + 150, strlen($content)-1);
                                     while($end_index < strlen($content)){
                                         if($content[$end_index] == ' ')
                                             break;
                                         $end_index = $end_index+1;
                                     }
                                    $snippet = substr($content,$start_index,$end_index - $start_index);
                                    break;
                                }
                            }
                            if(strlen($snippet) <= 1){
                              foreach($sentences as $sentence){
                                if(preg_match($all_query, $sentence)>0){
                                    $index = stripos($content, $sentence);
                                    $start_index = max(0, ($index + stripos($sentence, $words[0]) - 5));
                                    while($start_index > 0){
                                         if($content[$start_index] == ' ')
                                             break;
                                         $start_index = $start_index-1;
                                     }
                                     $end_index = min($start_index + 150, strlen($content)-1);
                                     while($end_index < strlen($content)){
                                         if($content[$end_index] == ' ')
                                             break;
                                         $end_index = $end_index+1;
                                     }
                                    $snippet = substr($content,$start_index,$end_index - $start_index);
                                    break;
                                }
                              }
                            }
                            
                            if(strlen($snippet) <= 1){
                                foreach($words as $word){
                                    $word = " ".$word." ";
                                    if(stripos($content, $word) != false) {
                                        $index = stripos($content, $word);
                                        $index += 1;//adjust for one whitespace
                                        $start_index = max(0,$index - 80);
                                        while($start_index != 0){
                                            if($content[$start_index] == ' ')
                                                break;
                                            $start_index = $start_index-1;
                                        }
                                        $end_index = min($start_index + 155, strlen($content)-1);
                                        while($end_index < strlen($content)){
                                            if($content[$end_index] == ' ')
                                                break;
                                            $end_index = $end_index+1;
                                        }
                                        $snippet = substr($content,$start_index,$end_index - $start_index);
                                        break;
                                    }
                                }
                            }
                            foreach($words as $word){
                                $snippet = preg_replace('/\b'.$word.'\b/i',"<span class='snippet'>\$0</span>",$snippet);
                            }
                            echo "<div class = italian>...".$snippet."...</div>";

                            ?>
                        </div>
                    </li>
                    <?php
                }
                ?>
            </ol>
        </td>
    </table>
    <?php
}
?>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- AutoComplete-->
    <script>
        function autoCompletion(input, e) {
            var URL_prefix = "http://127.0.0.1:8983/solr/myexample/suggest?q=";
            var terms = input.split(" ");
            var search_term = terms[terms.length-1].toLowerCase();
            var URL = URL_prefix + search_term;
            $("#q").autocomplete({
                minLength: 1,
                source : function(request,response) {
                    $.ajax({
                        type: "GET",
                        url: URL,
                        dataType: "jsonp", //Cross-domain
                        jsonp: 'json.wrf',
                        success : function(res) {
                            var suggestions=res["suggest"]["suggest"][search_term]["suggestions"];
                            var suggestions_list = [];
                            for(var i = 0; i < 5; i++){
                                suggestions_list.push(input.substr(0,input.lastIndexOf(" ")+1) + suggestions[i]["term"]);
                            }
                            response(suggestions_list);
                        }
                    });
                }
            });
            if (event.keyCode == "13") {
                $('#go').click();
            }
        }
    </script>
</body>
</html>