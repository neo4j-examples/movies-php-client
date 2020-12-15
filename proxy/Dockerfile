FROM nginx:latest

LABEL maintainer="Ghlen Nagels <ghlen@pm.me>"

RUN rm /etc/nginx/conf.d/default.conf

COPY proxy/nginx.conf /etc/nginx/nginx.conf
COPY proxy/conf.d/ /etc/nginx/conf.d/
COPY public/ /srv/app/public/


