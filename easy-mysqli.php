<?php
error_reporting(E_ALL & ~E_NOTICE);
class database {
	public $dbconnect;
	public $dbquery;
	public $dbresult;
	public $dbqueryArr = array();
	private $pages_counter;
	private $query_buffer = array();
	private $reference;
	private $dbconnection = array();
	public $prefix = '';
	public $querySQL = '';
	public $totalRecords =  1;
	public function __construct($server, $username, $password, $database, $prefix = ''){
		$this->connect($server, $username, $password, $database, $prefix);
	}
	public function connect($server, $username, $password, $database, $prefix = ''){
		$this->prefix = $prefix;
		$this->dbconnect = new mysqli($server, $username, $password, $database);		
		try{
			if (mysqli_connect_error())
			  throw new Exception("Failed to connect to MySQL: (" . mysqli_connect_errno() . ") " . mysqli_connect_error());
		}
		catch(Exception $e1)
		{
			echo $e1->getMessage()."<br />\n";
			echo '<textarea name="MYSQL_ERROR" cols="100" rows="10">'.$e1->getTraceAsString()."</textarea><br />\n";
			exit;
		}
		$this->dbconnect->set_charset('utf8');
		return $this->dbconnect;
	}
	public function disconnect(){
		if($this->dbconnect == TRUE)
		 $this->dbconnect->close();
	}
	public function query($sql='', $qryArr=FALSE){
		try{
			$sql = str_ireplace('#prefix#', $this->prefix, $sql);
			if(($queryid = @$this->dbconnect->query($sql, MYSQLI_STORE_RESULT)) == FALSE)			
			   throw new Exception('Seems there is an error in Structured Query Language');
			if($qryArr != FALSE){
				$this->dbqueryArr["$qryArr"] = $queryid;
			}else{
				 $this->dbquery = $queryid;
			}
		}catch(Exception $e3){
			echo $e3->getMessage()."<br />\n";
			echo '<pre dir="ltr">'.$e3->getTraceAsString()."\nQUERY: ".$sql."</pre>\n";
			echo '<p dir="ltr">MySQLi Error: '.$this->dbconnect->error."</p>\n";
			exit;
		}
	}
	public function multi_query($sql=''){
		$sql = str_ireplace('#prefix#', $this->prefix, $sql);
		if(!$this->dbconnect->multi_query($sql)){
			echo ($this->dbconnect->errno.': '.$this->dbconnect->error);
		}
		do {
			if ($res = $this->dbconnect->store_result()) {
				var_dump($res->fetch_all(MYSQLI_ASSOC));
				$res->free();
			}
		} while ($this->dbconnect->more_results() && $this->dbconnect->next_result());
	}
	public function fetch_array($qryArr = false, $nameOnly = false){
		$queryid = ($qryArr != FALSE)? $this->dbqueryArr["$qryArr"] : $this->dbquery;
		$this->dbresult = mysqli_fetch_array($queryid, ($nameOnly? MYSQLI_ASSOC : MYSQL_BOTH));
		return $this->dbresult;
	}
	public function num_rows($qryArr=FALSE){
		$queryid = ($qryArr != FALSE)? $this->dbqueryArr["$qryArr"] : $this->dbquery;
		$this->dbresult = $queryid->num_rows;
		return $this->dbresult;
	}
	public function insert_id(){
		return $this->dbconnect->insert_id;
	}	
	public function fetch_fields($qryArr=FALSE)	{
		$queryid = $qryArr != FALSE? $this->dbqueryArr["$qryArr"] : $this->dbquery;
		return $queryid->fetch_field();
	}
	public function count_records($query, $qryArr=FALSE){
		$queryid = $qryArr != FALSE? $this->dbqueryArr["$qryArr"] : $this->dbquery;
		$query = substr_replace($query, 'COUNT(*) as total', strpos($query, '*'), 1);
		self::query($query, $qryArr);
		$total = self::fetch_array($qryArr);
		return $total['total'];
	}
	public function query_limit($counting, $page = '', $limit = ''){
		$limit = empty($limit)? $this->setting['recordppage'] : $limit;
		//$limit = 1;
		$maximum = ceil($counting/$limit);
		if($page > $maximum) 
		  $page = $maximum;
		$page = (!empty($page) && is_numeric($page))? ($page-1)*$limit : '0' ;
		return $page.','.$limit;
	}
	public function pagination_number($counting, $limit = ''){
		$limit = empty($limit)? $this->setting['recordppage'] : $limit;
		$maximum = ceil($counting/$limit);
		return $maximum;		
	}
	public function escape_string($input){
		$output = $this->dbconnect->real_escape_string($input);
		return $output? $output : $input;
	}
	public function create_query(){
		$prefix = strrpos($this->query_buffer['fields'], '.') !== false? '#prefix#' : '';
		$query = 'select ';
		$fields_string = '';
		$results_counter = '';
		if(!empty($this->query_buffer['fields'])){
			if(strrpos($this->query_buffer['fields'], ',') !== false){
				$fieldsArray = @explode(',', $this->query_buffer['fields']);
				foreach($fieldsArray as $field){
					$query .= trim($prefix).trim($field).', ';
				}
				$query = substr($query, 0, -2).' ';
			}else{
				$query .= $prefix.$this->query_buffer['fields'];
			}
		}else{
			$query .= '* ';
		}
		$fields_string = str_replace('select ', '', $query);
		if(!empty($this->query_buffer['selectAppend'])){
			$query .= ', '.$this->query_buffer['selectAppend'];
		}
		if(!empty($this->query_buffer['table'])){
			$query .= ' from #prefix#'.$this->query_buffer['table'].' ';
			$results_counter .= ' from #prefix#'.$this->query_buffer['table'].' ';
		}
		if(is_array($this->query_buffer['join']) && is_array($this->query_buffer['on']) && @count($this->query_buffer['join']) == @count($this->query_buffer['on']) && @count($this->query_buffer['join']) > 0){
			foreach($this->query_buffer['join'] as $i => $table){
				if(is_array($this->query_buffer['on'][$i])){
					$query .= $this->query_buffer['side'][$i].' join #prefix#'.$table.' on #prefix#'.$this->query_buffer['on'][$i][0].' = #prefix#'.$this->query_buffer['on'][$i][1].' ';
				}else{
					$query .= $this->query_buffer['side'][$i].' join #prefix#'.$table.' on '.$this->query_buffer['on'][$i].' ';
				}
			}
		}
		if(is_array($this->query_buffer['criteria']) && is_array($this->query_buffer['criteria_values']) && @count($this->query_buffer['criteria']) == @count($this->query_buffer['criteria_values']) && @count($this->query_buffer['criteria']) > 0){
			// || (is_array($this->query_buffer['join']) && @count($this->query_buffer['join']) > 0)
			$prefix = strpos($query, 'inner join') !== false? '#prefix#' : $prefix;
			$query .= 'where ';
			foreach($this->query_buffer['criteria'] as $i => $criteria){
				$query .= $prefix.$criteria.' '.$this->query_buffer['criteria_values'][$i].' && ';
			}
			$query = substr($query, 0, -3).' ';
			
		}
		if(is_array($this->query_buffer['match']) && count($this->query_buffer['match']) > 0){
			if(strrpos($query, 'where') !== false){
				$query .= ' && ';
			}else{
				$query .= 'where ';
			}
			foreach($this->query_buffer['match'] as $match){
				$query .= ''.$match.' && ';
			}
			$query = substr($query, 0, -3).' ';
		}
		if(!empty($this->query_buffer['custom_condition'])){
			$query .= $this->query_buffer['custom_condition'].' ';
		}
		$results_counter = $query;
		if(!empty($this->query_buffer['group'])){
			$query .= 'group by '.$this->query_buffer['group'].' ';
		}
		if(!empty($this->query_buffer['sorting'])){
			$query .= 'order by '.$this->query_buffer['sorting'].' ';
		}
		if(!empty($this->query_buffer['limit'])){
			$query .= 'limit '.$this->query_buffer['limit'];
		}
		if(!empty($this->query_buffer['pages']) && $this->query_buffer['pages'] > 0){
			$countingQuery = str_replace($fields_string, '*', $query);
			$countingQuery = preg_replace("/select *\s(.*)\sfrom/", '', $countingQuery);
			$countingQuery = substr($countingQuery, 0, strpos($countingQuery, 'order by'));
			if(strpos($countingQuery, 'select *') === false){
				$countingQuery = 'select * from'.$countingQuery;
			}
			
			//$counting = isset($this->query_buffer['total']) && $this->query_buffer['total'] > 0? $this->query_buffer['total'] : $this->db->count_records(str_replace($fields_string, '*', $query));
			$counting = isset($this->query_buffer['total']) && $this->query_buffer['total'] > 0? $this->query_buffer['total'] : $this->count_records($countingQuery);
			$query .= 'limit '.$this->query_limit($counting, $this->query_buffer['currentPage'], $this->query_buffer['pages']);
			$this->totalRecords = $counting;
		}
		$this->querySQL = $sql = str_ireplace('#prefix#', $this->prefix, $query);
		$this->clean_buffer();
		return $query;
	}
	public function select($fields = ''){
		$this->query_buffer['fields'] = $fields;
		return $this;
	}
	public function selectAppend($input){
		$this->query_buffer['selectAppend'] = $input;
		return $this;
	}
	public function from($table){
		$this->query_buffer['table'] = $table;
		return $this;
	}
	public function where($criteria){
		if(!is_array($this->query_buffer['criteria'])){
			$this->query_buffer['criteria'] = array();
			$this->query_buffer['criteria_values'] = array();
		}
		array_push($this->query_buffer['criteria'], $criteria);
		return $this;
	}
	public function match($fields, $keywords){
		if(!is_array($this->query_buffer['match'])){
			$this->query_buffer['match'] = array();
		}
		if(!empty($fields)){
			$keywords = $this->escape_string($keywords);
			$keywords = str_replace('&quot;', '"', $keywords);
			array_push($this->query_buffer['match'], 'match('.$fields.') against(\'*'.$keywords.'*\' in boolean mode)');
		}
		return $this;
	}
	public function is($operation = '=', $value){		
		if(!is_array($this->query_buffer['criteria_values'])){
			$this->query_buffer['criteria_values'] = array();
		}
		switch($operation){
			case 'like': $term = 'like \'%'.$this->escape_string($value).'%\' '; break;
			case 'likeAnd': $term = 'like \'%'.str_replace('&amp;', '&', $this->escape_string($value)).'%\' '; break;
			case 'fnEq': $term = '= '.stripslashes($this->escape_string($value)).' '; break;
			case 'fnGt': $term = '> '.stripslashes($this->escape_string($value)).' '; break;
			case 'fnGtEq': $term = '>= '.stripslashes($this->escape_string($value)).' '; break;
			case 'fnLt': $term = '< '.stripslashes($this->escape_string($value)).' '; break;
			case 'fnLtEq': $term = '<= '.stripslashes($this->escape_string($value)).' '; break;
			case 'inArray': if(is_array($value) && count($value) > 0){ $term = 'in ('.$this->escape_string(implode(',', $value)).') '; }; break;
			case 'inArrayQ': if(is_array($value) && count($value) > 0){ $term = 'in (\''.stripslashes($this->escape_string(implode('\',\'', $value))).'\') '; }; break;
			case '!inArray': if(is_array($value) && count($value) > 0){ $term = 'not in ('.$this->escape_string(implode(',', $value)).') '; }; break;
			case '!inArrayQ': if(is_array($value) && count($value) > 0){ $term = 'not in (\''.stripslashes($this->escape_string(implode('\',\'', $value))).'\') '; }; break;
			default: $term = $operation.' \''.$this->escape_string($value).'\' '; break;
		}
		//$term = $operation == 'like'? 'like \'%'.$this->escape_string($value).'%\' ' : $operation.' \''.$this->escape_string($value).'\' ';
		array_push($this->query_buffer['criteria_values'], $term);
		return $this;
	}
	public function join($table, $side = ''){
		if(!is_array($this->query_buffer['join'])){
			$this->query_buffer['join'] = array();
			$this->query_buffer['on'] = array();
			$this->query_buffer['side'] = array();
		}
		array_push($this->query_buffer['join'], $table);
		array_push($this->query_buffer['side'], empty($side)? 'inner' : $side);
		return $this;
	}
	public function on($fields){
		if(!is_array($this->query_buffer['on'])){
			$this->query_buffer['on'] = array();
		}
		array_push($this->query_buffer['on'], $fields);
		return $this;
	}
	public function order($sorting){
		$this->query_buffer['sorting'] = $sorting;
		return $this;
	}
	public function pages($limit, $currentPage = 1){
		$this->query_buffer['pages'] = (int) $limit;
		$this->query_buffer['currentPage'] = (int) $currentPage;
		unset($this->query_buffer['limit']);
		return $this;
	}
	public function limit($number){
		if(!isset($this->query_buffer['pages'])){
			$this->query_buffer['limit'] = $number;
		}
		return $this;
	}
	public function group($group){
		$this->query_buffer['group'] = $group;
		return $this;
	}
	public function total($total){
		$this->query_buffer['total'] = $total;
		return $this;
	}
	public function custom_condition($query){
		$this->query_buffer['custom_condition'] = $query;
		return $this;
	}
	public function execute($reference = ''){
		$this->query($this->create_query(), $reference);
		$this->reference = $reference;
		return $this;
	}
	public function fetch($reference = ''){
		$this->reference = $reference;
		return $this->fetch_array($this->reference, 1);
	}
	public function clean_buffer(){
		if(is_array($this->query_buffer) && @count($this->query_buffer) > 0){
			$buffer = $this->query_buffer;
			foreach($buffer as $index => $data){
				unset($this->query_buffer[$index]);
			}
		}
	}
	
}
## Initialization (Server, Username, Password, Database Name, Prefix if applicable)
$db = new database('localhost', 'root', 'root', 'epcms', 'epcms_');

## Baisc query 
$db->select('*')->from('user')->execute();

## Use where clause
$db->select('*')->from('user')
   ->where('username')->is('like', 't')
   ->where('usergroup')->is('>', 0)
   ->execute();

## Use where clause within array of user ids
$db->select('*')->from('user')
   ->where('userid')->is('inArray', array(1,2,3))  ## to exculde use "inArray" instead of "inArray"
   ->execute();

## Use where clause with mysql function
$db->select('*')->from('user')
   ->where('password')->is('fnEq', "MD5('admin')")
   ->execute();

## Fulltext search
$db->select('*')->from('user')
   ->match('firstname, lastname', 'Test')
   ->execute();

## Join another table
$db->select('*')->from('user')
   ->join('ims_user')->on(array('user.userid', 'ims_user.userid')) ## for sided join join('ims_user', 'left')
   ->execute();

## Order results
$db->select('*')->from('user')->order('username ASC, userid ASC')->execute();

## Group results
$db->select('*')->from('user')->group('usergroup')->execute();

## Group results with count
$db->select('*, count(*) as total')->from('user')->group('usergroup')->execute();

## Pagination
$db->select('*')->from('user')->pages(10, 1)->order('userid ASC')->execute(); ## pages(Limit per page, current page number)

echo '<code>'.$db->querySQL.'</code>';
if($db->num_rows() > 0){ 
	while($row = $db->fetch()){
		echo '<pre>'.print_r($row, true).'</pre>';
	}
}

## Disconnect database
$db->disconnect();
?>