FROM debian:stretch-slim

MAINTAINER Ilgıt Yıldırım <ilgit.yildirim@triplebits.com>

RUN apt-get update && apt-get install -y nginx && apt-get install -y curl

RUN rm -rf var/www/html && rm /etc/nginx/sites-enabled/default

ADD etc /etc/

RUN ln -sf /dev/stdout /var/log/nginx/access.log && ln -sf /dev/stderr /var/log/nginx/error.log

RUN usermod -u 1000 www-data

RUN chown www-data:1000 /var/www -R

CMD ["nginx"]

EXPOSE 80 443
