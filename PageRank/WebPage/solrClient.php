<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');
include 'SpellCorrector.php';
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
//        $queryterms = explode(" ",$query);
//        $original_query = $query;
//        $query = "";
//        $flag = 0;
//        $fg =isset($_REQUEST['f']) ? true : false;
//        if($fg == false){
//            foreach($queryterms as $term){
//                $t = SpellCorrector::correct($term);
//                $t = SpellCorrector::correct($term);
//                if(trim(strtolower($t)) != trim(strtolower($term))){
//                    $flag = 1;
//                }
//                $query = $query." ".$t;
//            }
//            $query = trim($query);
//        }else{
//            $query = $original_query;
//        }

        $additionalPara = array('sort' => 'pageRankFile desc');
        $pr_results = $solr->search($query, 0, $limit, $additionalPara);
        $results = $solr->search($query, 0, $limit);


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
    </style>
</head>
<body>
<form class = "point" accept-charset="utf-8" method="get" >
    <label for="q">Search:</label>
    <input id="q"  name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" style="width: 500px;"/>
    <input type="submit" value="GO!">
</form>

<?php
if($query){
    $query = strtolower($query);
    $terms = explode(" ",$query);
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
            ?>
            <h2> Showing results for: <a href="solrClient.php?q=<?=$correct_terms?>"><?= $correct_terms; ?></a></h2>
            <?php

        }
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

                            echo "<a href = '{$url}'><h2>".$title."</h2></a>";
                            echo "URL: <a href = '{$url}'>".$url."</a></br></br>";
                            echo "Id: ".$id. "</br></br>";
                            echo "Description: ".$description."</br></br>";
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
                                    $pr_url = $url_set[str_replace("/Users/zijianli/Documents/solr-7.1.0/NYD/", "", $id)];
                                }
//                                if($field == "og_url"){
//                                    $pr_url = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
//                                }
//                                if($pr_url == "")
//                                    $pr_url = "N/A";
                            }

                            echo "<a href = '{$pr_url}'><h2>".$pr_title."</h2></a>";
                            echo "URL: <a href = '{$pr_url}'>".$pr_url."</a></br></br>";
                            echo "Id: ".$pr_id. "</br></br>";
                            echo "Description: ".$pr_description."</br></br>";
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
</body>
</html>