# XtreamUI
Xtream Codes 2.93 - Admin Interface

To update run this command from your XC server as root:
-----------------------------------------------
apt-get install unzip -y;rm -rf /home/xtreamcodes/iptv_xtream_codes/admin && rm -rf /home/xtreamcodes/iptv_xtream_codes/pytools && rm -rf /home/xtreamcodes/iptv_xtream_codes/adtools ; wget https://github.com/xtreamui/XtreamUI/archive/master.zip -O /tmp/update.zip -o /dev/null && unzip /tmp/update.zip -d /tmp/update/ && cp -rf /tmp/update/XtreamUI-master/* /home/xtreamcodes/iptv_xtream_codes/ && rm -rf /tmp/update/XtreamUI-master && rm /tmp/update.zip && rm -rf /tmp/update && rm /home/xtreamcodes/iptv_xtream_codes/README.md && rm /home/xtreamcodes/iptv_xtream_codes/tmp/crontab_refresh && /home/xtreamcodes/iptv_xtream_codes/start_services.sh

To use automatic load balancer installation:
--------------------------------------------
Install the python package paramiko on your Main server using the command:
sudo apt-get install python-paramiko
