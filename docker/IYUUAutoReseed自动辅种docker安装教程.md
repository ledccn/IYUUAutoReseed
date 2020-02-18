# IYUUAutoReseed自动辅种docker安装教程

第一步：复制docker目录到您的Linux的任意目录内；

第二步：给予`build.sh`、`iyuu.sh`可执行权限；

第三步：编译镜像并运行容器，命令为：`./build.sh` 耐心等待完成；

第四步：测试是否安装完成，命令为：`./iyuu.sh`

然后看教程：https://www.iyuu.cn/archives/324/，来编辑配置即可。

#### 必读：脚本会在`/root`目录，创建`IYUUAutoReseed`文件夹，您只需要按照上述教程编辑好配置，放到`/root/IYUUAutoReseed/config/config.php`



### 辅种时执行的命令：`iyuu.sh`



## 如何定时辅种？

把`iyuu.sh`加入Linux计划任务内。