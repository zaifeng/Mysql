###创建用户
使用最高级（非必须）用户创建用户
>create user 'zaifeng'@'localhost' identified by 'password';
创建用户 zaifeng 密码 password（自行修改） 仅限本机访问，localhost可替换为ip、网段等

###授权
>grant select,update,insert,delete on notebook.* to 'zaifeng'@'localhost';
主要有select,insert,update,delete,create,drop,index,alter,grant,references,reload,shutdown,process,file等14个权限


### 刷新权限系统
>flush privileges;
当新权限或用户增加时，是不会马上生效的，必须用这个命令，使新添加用户或权限马上生效

###查看当前数据库
>select database();
+------------+
| database() |
+------------+
| notebook   |
+------------+

###修改用户密码
