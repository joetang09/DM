# DM

一个 php 的数据迁移工具

## Guide

1. 前往 ./Config/ 配置 WorkerManagerConfig.php 

    > loop  是否循环执行
    
    > loopStopTimeInterval 循环执行的停止时间差
    
    > needReleaseResourceInterface 当fork 子进程的时候，需要进行资源释放的接口
    
2. 前往 ./Config/ 配置 SystemConfig.php
    
    > deamonize  是否守护进程
    
3. 前往 ./Config/ 配置 DbConfig.php

    > 类似 source 的配置，如果你需要数据库的话
    
4. 前往 ./WorkerFactory 编写迁移对应代码
    
    > 需要继承 **\Core\AbsWorker** ，并实现 **fetchRawMaterial** ， **dealOne**          两个方法
    
    > 选择性的重载 **runBefore** **runAfter** 方法

5. 前往 ./Config/ 配置对应 Worker 的配置，命名 {WorkerName}Config

    > runSort 执行的顺序
    
    > dealNumberTimes 每次执行的数目
    
    ```
    Tips:
    1. 如果两个worker runSort 一样的话，那么将并发执行
    ```

6. 启动

    > ./dm 

    ```
    Tips:
    1. -d  守护进程
    2. status 查看状态

    ```