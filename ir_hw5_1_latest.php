<?php
// make sure browsers see this page as utf-8 encoded HTML
ini_set('memory_limit',-1);
include 'SpellCorrector.php';

header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;

$results = false;

$div = false;

$output = "";
$oldworld = "";

$newWord = "";


$fileUrl = array();
$fp = fopen('URLtoHTML_latimes.csv', 'r');
if ($fp != false)
{
	while($val= fgetcsv($fp,0,","))
	{
		#$k = "/Users/ishanisahay/solr-7.5.0/latimesfiles/latimes/".$val['0'];
		$k = $val['0'];
		$v = $val['1'];
		$fileUrl[$k] = $v;

	}
	fclose($fp);
}


//var_dump($arr);
if ($query)
{
  $searchType = isset($_REQUEST['typeOfSearch'])? $_REQUEST['typeOfSearch'] : "default";
 // The Apache Solr Client library should be on the include path
 // which is usually most easily accomplished by placing in the
 // same directory as this script ( . or current directory is a default
 // php include path entry in the php.ini)
 require_once('Apache/Solr/Service.php');
 // create a new solr service instance - host, port, and corename
 // path (all defaults in this example)
 $solr = new Apache_Solr_Service('localhost', 8983, '/solr/latimescore/');
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
    if($searchType == "pagerank")
    {
    	$additionalParameters=array('sort' => 'pageRankFile desc');
    }
    else{
      $additionalParameters=array('sort' => '');
    }

    $word = explode(" ",$query);
    $spell = $word[sizeof($word)-1];

    for($i = 0; $i < sizeOf($word); $i++) {

      ini_set('max_execution_time', 300);
      ini_set('memory_limit',-1);

      $che = SpellCorrector::correct($word[$i]);

      if($oldworld != "")

        $oldworld = $oldworld."+".trim($che);

      else{

        $oldworld = trim($che);
      }
        $newWord = $newWord." ".trim($che);
    }

    $newWord = str_replace("+"," ",$oldworld);
    $div = false;

    if(strtolower($query) == strtolower($newWord)){
      
      $results = $solr->search($query, 0, $limit, $additionalParameters);
    }
    else {

      $div =true;

      $results = $solr->search($query, 0, $limit, $additionalParameters);

      $url = "http://localhost/~ishanisahay/ir_hw5_1_latest.php?q=$oldworld&typeOfSearch=$searchType";

      $output = "Did you mean: <a href='$url'>$newWord</a>";

    }
 //$results = $solr->search($query, 0, $limit);
 // $param = array('sort' => 'pageRankFile desc');
 // $results = $solr->search($query, 0, $limit, $param);
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
<html>
  <head>
    <title>Search Engine</title>

    <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  </head>

  <body>

     <h1 align="center">Search Engine </h1><br/>

    <form accept-charset="utf-8" method="get" id="search_form" align="center">

      Search: <input id="q" type="text"  name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" list="resultsSearch" placeholder="search solr " autocomplete="off"/>

      <datalist id="resultsSearch"></datalist>

      <input type="hidden" id="spellCheckor" name="spellCheckor" value="false"> <br><br>

      <input type="radio" name="typeOfSearch" <?php if (isset($_GET['typeOfSearch']) && $_GET['typeOfSearch']=="lucene") echo 'checked="checked"';?>  value="lucene" /> Lucene (Default)

	   <input type="radio" name="typeOfSearch" <?php if (isset($_GET['typeOfSearch']) && $_GET['typeOfSearch']=="pagerank") echo 'checked="checked"';?> value="pagerank" /> PageRank <br><br>

      <input type="submit" value="Submit"/>

    </form>
    <script>

   $(function() {

     var urlSuffix = "&wt=json&indent=true";

     var urlPrefix = "http://localhost:8983/solr/latimescore/suggest?q=";

     var resp = [];
     
     $("#q").autocomplete({

       source : function(request, response) {

         var query = $("#q").val().toLowerCase();

         var character_count = query.length - (query.match(/ /g) || []).length;

         var newword = "";
         var before = "";
         
          
         var indexofspace =  query.lastIndexOf(' ');

         if( indexofspace != -1 && query.length-1 > indexofspace) {

          newword = query.substr(indexofspace + 1);
          before = query.substr(0, indexofspace);

        }
        else {

          newword = query.substr(0);

        }

        var URL = urlPrefix + newword + urlSuffix;

        $.ajax({
         url : URL,
         success : function(data) { 

          var ret = data.suggest.suggest;

          var docString = JSON.stringify(ret);

          var jsonData = JSON.parse(docString);

          var suggestedresult =jsonData[newword].suggestions;
          
          var mainword =[];
          var j=0;

          for(var i=0; i < 5 && j < suggestedresult.length; i++,j++){
            if(suggestedresult[j].term == newword)
            {
              i = i-1;
              continue;
            }
            for(var k = 0; k<i && i>0; k++){

              if( resp[k].indexOf(suggestedresult[j].term) >=0 ){
                i = i-1;
                continue;
              }
            }
            if(suggestedresult[j].term.indexOf('_') >= 0 || suggestedresult[j].term.indexOf('.') >= 0)
            {
              i = i-1;
              continue;
            }

            var s = (suggestedresult[j].term);

            if(mainword.length == 5)
              break;

            if(mainword.indexOf(s) == -1)
            {
              mainword.push(s);
              if(before == ""){
                resp[i] = s;
              }
              else
              {
                resp[i] = before+" ";
                resp[i] += s;
              }
            }
          }

          console.log(resp);
          response(resp);
        },
        dataType : 'jsonp',
        jsonp : 'json.wrf'
      });
      },
      minLength : 1
    })
   });
 </script>
<?php

if ($div) {

  echo $output;
}


$pre = "";
// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {

    $myId = is_string($doc->id) ? $doc->id : $doc->id[0];
    #$or_id = $myId;
    $myId = str_replace("/Users/ishanisahay/solr-7.5.0/latimesfiles/latimes/","",$myId);

    $myDescription = is_string($doc->og_description) ? $doc->og_description : $doc->og_description[0];

    $myTitle = is_string($doc->title) ? $doc->title : $doc->title[0];   

	$myUrl = is_string($doc->og_url) ? $doc->og_url : $doc->og_url[0];


  $temp = htmlentities($myDescription, null, 'utf-8');

	$finalDesc = str_replace("&nbsp;", "", $temp);

	$finalDesc = html_entity_decode($finalDesc);

 	if(empty(trim($finalDesc))) 
 	{
 		$finalDesc =  "N/A";
 	} 

	$myUrl = htmlentities($myUrl, null, 'utf-8'); 	
 	$myUrl = str_replace("&nbsp;", "", $myUrl);
 	$myUrl = html_entity_decode($myUrl);

 	if(empty(trim($myUrl)))
 	{

 		$myUrl = $fileUrl[$myId];
 	
 	}
 	
    //check
    $queryTerm = $_GET["q"];//search content

    $ar = explode(" ", $queryTerm);

    $files_dir = "/Users/ishanisahay/solr-7.5.0/latimesfiles/latimes/";

    $filename = $files_dir . $myId;

    $html = file_get_contents($filename);

    $lines = explode(".", $html);

    $wordsofquery = explode(" ", $query);

    $snippet = "";

    $endlimiter = "\b)";
    $querystring = "/";
    $startlimiter = "(?=.*?\b";
    

    foreach($wordsofquery as $item){

      $querystring = $querystring.$startlimiter.$item.$endlimiter;
    }

    $querystring = $querystring."^.*$/i";

    foreach($lines as $line) {

      $line = strip_tags($line);
      $line = preg_replace("/[^A-Za-z]/", ' ', $line);

      if (preg_match($querystring, $line)>0){


        if (preg_match("(&gt|&lt|\/|{|}|[|]|\|\%|>|<|:)",$line)>0) {
          
          continue;

        }
        else{

          $snippet = $snippet.$line;

          if(strlen($snippet)>160)
            break;
        }
      }
    }
    
  $wordsofquery = preg_split('/\s+/', $query);

  foreach($wordsofquery as $item)
  {
  	
	$snippet = str_ireplace($item, "<strong>".$item."</strong>",$snippet);

    if ($snippet == "") {

      $snippet = "N/A";
    }
    

  }


  
  //check
?>
      <li>
        	
          <p>
          	<span><strong><?php echo htmlspecialchars("Title", ENT_NOQUOTES, 'utf-8'); ?></strong></span>
            <?php echo "<a target='_blank' href = '{$myUrl}' STYLE='text-decoration:none'><font size='4px'><b>".$myTitle."</b></font></a>" ?>
          </p>

          <p>
            <span><strong><?php echo htmlspecialchars("Snippet", ENT_NOQUOTES, 'utf-8'); ?></strong></span>
            <?php
            if($snippet == "N/A"){
              echo htmlspecialchars($snippet, ENT_NOQUOTES, 'utf-8');
            }else{
              echo "...".$snippet."...";
            }
            ?>
          </p>

          <p>
            <span><strong><?php echo htmlspecialchars("Description", ENT_NOQUOTES, 'utf-8'); ?></strong></span>
            <?php echo htmlspecialchars($finalDesc, ENT_NOQUOTES, 'utf-8'); ?>
          </p>

          <p>
            <span><strong><?php echo htmlspecialchars("OutgoingUrl", ENT_NOQUOTES, 'utf-8'); ?></strong></span>
            <?php echo "<a target='_blank' href = '{$myUrl}' STYLE='text-decoration:none'>$myUrl</a>" ?>
          </p>
          <p>
            <span><strong><?php echo htmlspecialchars("Id", ENT_NOQUOTES, 'utf-8'); ?></strong></span>
            <?php echo htmlspecialchars($myId, ENT_NOQUOTES, 'utf-8'); ?>
          </p>
       
         
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>
