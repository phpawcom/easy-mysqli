# Easy MySQLi Class
to be used for beginner developers

# Getting Started
- Download class file to your directory
- Include class file
- Connect to the database!

```php
<?php
include('easy-mysqli.php');
## Initialization (Server, Username, Password, Database Name, Prefix if applicable)
$db = new database('localhost', 'root', 'root', 'epcms', 'epcms_');

## Disconnect database
$db->disconnect();
?>
```

#### Basic Query:
```php
$db->select('*')->from('user')->execute();
```
#### Use where clause:
```php
$db->select('*')->from('user')
->where('username')->is('like', 't')
->where('usergroup')->is('>', 0)
->execute();
```
#### Use where clause within array of user ids:
```php
$db->select('*')->from('user')
->where('userid')->is('inArray', array(1,2,3))  ## to exculde use "inArray" instead of "inArray"
->execute();
```
#### Use where clause with mysql function:
```php
$db->select('*')->from('user')
->where('password')->is('fnEq', "MD5('admin')")
->execute();
```
#### Fulltext search:
```php
$db->select('*')->from('user')
->match('firstname, lastname', 'Test')
->execute();
```
#### Join another table:
```php
$db->select('*')->from('user')
->join('ims_user')->on(array('user.userid', 'ims_user.userid')) ## for sided join join('ims_user', 'left')
->execute();
```
#### Group results:
```php
$db->select('*')->from('user')->group('usergroup')->execute();
```
#### Group results with count():
```php
$db->select('*, count(*) as total')->from('user')->group('usergroup')->execute();
```
#### Order results:
```php
$db->select('*')->from('user')->order('username ASC, userid ASC')->execute();
```
#### Pagination:
```php
## NOTE: it must have an order to work properly
$db->select('*')->from('user')->pages(10, 1)->order('userid ASC')->execute(); ## pages(Limit per page, current page number)
```

#### Where()->is() staff:
| Operation | Meaning/Description |
| ------ | ------ |
| like | column like '%%' |
| fnEq, fnGt, fnGtEq, fnLt, fnLtEq | for (>,<,=) when it be used with mysql function |
| inArray | column in() |
| !inArray | column not in()  |
| inArrayQ, !inArrayQ | column (not) in ('', '') // with quotes when use strings |

Feel free to use/modify class!



# easy-mysqil
