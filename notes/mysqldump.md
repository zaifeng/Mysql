1、备份MySQL数据库的命令
```
mysqldump -hhostname -uusername -ppassword databasename > backupfile.sql
```

2、备份MySQL数据库为带删除表的格式
备份MySQL数据库为带删除表的格式，能够让该备份覆盖已有数据库而不需要手动删除原有数据库。
```
mysqldump --dd-drop-table -uusername -ppassword databasename > backupfile.sql
```

3、直接将MySQL数据库压缩备份（这个超方便，可以直接打包下载）
```
mysqldump -hhostname -uusername -ppassword databasename | gzip > backupfile.sql.gz
```
4、备份MySQL数据库某个(些)表
```
mysqldump -hhostname -uusername -ppassword databasename specific_table1 specific_table2 > backupfile.sql
```

5、同时备份多个MySQL数据库
```
mysqldump -hhostname -uusername -ppassword -databases databasename1 databasename2 databasename3 > multibackupfile.sql
```

6、仅仅备份数据库结构 -d == --no-data
```
mysqldump -d -databases databasename1 databasename2 databasename3 > structurebackupfile.sql
```
7、备份服务器上所有数据库
```
mysqldump -all-databases > allbackupfile.sql
```
8、还原MySQL数据库的命令
```
mysql -hhostname -uusername -ppassword databasename < backupfile.sql
```

9、还原压缩的MySQL数据库
```
gunzip < backupfile.sql.gz | mysql -uusername -ppassword databasename
```

10、将数据库转移到新服务器
```
mysqldump -uusername -ppassword databasename | mysql -host=*.*.*.* -C databasename
```