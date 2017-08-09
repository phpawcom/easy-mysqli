<?php
include('easy-mysqli.php');
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