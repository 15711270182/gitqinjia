<?php
return [
    'app\crontab\Certification',//增长认证人数
    'app\crontab\Results',//增长牵手结果人数
    'app\crontab\ReducedSign',//减少落地页报名人数
    'app\crontab\initInfoCheck',//完善信息审核
    'app\crontab\perfectInfoCheck',//初始化信息审核
    'app\crontab\EducationCheck',//学历审核
    'app\crontab\Recommend',//每日推荐
    'app\crontab\MsgPush',//离线未读消息推送
    'app\crontab\anewRunWeightScore',//模拟算法
    'app\crontab\getUserRecommendNew',//测试新算法
    'app\crontab\Night',//每日推荐  晚上10点
    'app\crontab\NightEnjoy',//每日喜欢  晚上8点
    'app\crontab\getRecPublicData',//更新公有池
    'app\crontab\activeUserScore',//活跃用户分数处理
    'app\crontab\RecommendReport',//推荐日报表

];
