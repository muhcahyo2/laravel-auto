FROM nginx:stable-alpine

COPY ./nginx /etc/nginx

RUN ln -sf /dev/stdout /var/log/nginx/access.log && ln -sf /dev/stderr /var/log/nginx/error.log