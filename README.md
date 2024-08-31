# serv00Helper
使用ssh登录实现serv00保活，带控制面板。
Python版本>=3.9，PHP73及以上，数据库使用SQLite3

# 部署
1. 上传所有文件到站点根目录
2. 安装Python环境。执行: `pip3 install paramiko`
3. 初始化数据库: `python ssh_test.py`
4. 设置站点根目录为public/，检查PHP版本（推荐PHP83）
5. 设置定时任务，每天0:00执行: `python ssh_test.py`
6. 浏览器打开控制面板(`https://your-domain.com/`)，添加ssh登录信息
7. 站点伪静态设置: (**必需**)

禁止访问 .db, .log, .py, .bak 文件
```
location ~* \.(db|log|py|bak)$ {
   deny all;
   return 404;
}
```

# 常见问题
1. requsts库缺失: `pip install requsts`
2. python版本问题: 将上述所有`python` `pip` 换为 `python3` `pip3`. 或者指定版本:`python3.9 ssh_test.py`
3. python相关库安装失败: 启用虚拟环境或使用Debian/Ubuntu/CentOS等系统
4. 我想设置邮件通知: 自行配置resend，记得设置“这不是垃圾邮件”
5. 我想配置Telegram Bot通知: 自行配置telebot

# 温馨提示
白嫖有度，请勿滥用！
本项目仅做学习交流使用，由本项目引起的任何问题本人一概不负责！
