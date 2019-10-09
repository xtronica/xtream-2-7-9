# XtreamUI
Xtream Codes 2.93 - Admin Interface

To update run this command from your XC server:
-----------------------------------------------
rm -r /home/xtreamcodes/iptv_xtream_codes/admin && rm -r /home/xtreamcodes/iptv_xtream_codes/pytools && wget https://github.com/xtreamui/XtreamUI/archive/master.zip -O /tmp/update.zip -o /dev/null && unzip /tmp/update.zip -d /tmp/update/ && mv /tmp/update/XtreamUI-master/* /home/xtreamcodes/iptv_xtream_codes/ && rm /tmp/update.zip && rm -r /tmp/update && chmod -R 0777 /home/xtreamcodes/iptv_xtream_codes/admin && chmod -R 0777 /home/xtreamcodes/iptv_xtream_codes/pytools && chown -R xtreamcodes:xtreamcodes /home/xtreamcodes/iptv_xtream_codes/admin && chown -R xtreamcodes:xtreamcodes /home/xtreamcodes/iptv_xtream_codes/pytools && rm /home/xtreamcodes/iptv_xtream_codes/README.md
