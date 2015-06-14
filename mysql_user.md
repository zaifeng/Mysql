###1.创建用户

1)通过CREATE USER创建

语法：CREATE USER 'user'@'host' IDENTIFIED BY 'pass';

user为要创建的用户

@'host' user访问数据库的ip，可以填写以下参数

    localhost 本机，即 mysql服务器

    IP地址 用户访问mysql机器的ip

    %       授权该用户可通过任意ip访问mysql

IDENTIFIED BY 'pass' 用户设置用户密码，如不设置 则无须密码即可访问

例子：

msyql>CREATE USER 'zaifeng'@'localhost' IDENTIFIED BY '123456';

2)直接向mysql.user表里插入数据

语法：insert into user (host,user,password) values ('localhost','zhangsan',password('123456'));

###2.DB授权与收回

1)授权

语法：GRANT rights ON db.table TO user@ip_address IDENTIFIED BY 'pass' WITH GRANT OPTION ;
    
    rights:select,insert,update,delete,create,drop,index,alter,grant,references,reload,shutdown,process,file等14个权限

    db.table 授权用户可以访问的数据库及表，例如db.*,*.*

    IDENTIFIED BY :设定访问密码

    WITH GRANT OPTION: 带授权，即，可以将rights再授予其他用户

例子：

    mysql>grant select,update,insert,delete on notebook.* to 'zaifeng'@'localhost';

2)收回权限

语法：REVOKE privilege ON db.table FROM 'username'@'host';

例子: REVOKE SELECT ON *.* FROM 'zhangsan'@'%';

###3.密码修改

1)使用mysqladmin语法：mysqladmin -u用户名 -p旧密码 password 新密码

例如：#mysqladmin -u root -p 123 password 456；

2)直接修改user表的用户口令：

语法：update mysql.user set password=password('newpass') where user="user" and host="hostorip";

实例：mysql>update user set password=password('123456') where user='root';

3)使用SET PASSWORD语句修改密码

语法：SET PASSWORD FOR 'username'@'host' = PASSWORD('newpassword');

如果是当前登陆用户用SET PASWORD = PASSWORD("newpassword");

实例：

mysql>set password for root@'localhost'=password('');

###4.删除用户

语法: DELETE FROM user WHERE user = "user_name" and host = "host_name" ;

实例：

mysql>delete from user where user='u_note' and host='localhost';

###5.权限刷新

mysql>flush privileges;
