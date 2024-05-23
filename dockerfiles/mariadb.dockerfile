FROM mariadb:lts

COPY ./dockerfiles/make-new-db /usr/local/bin

RUN chmod a+x /usr/local/bin/make-new-db