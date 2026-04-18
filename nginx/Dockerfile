FROM nginx:alpine

# openssl è necessario per generare il certificato self-signed all'avvio
RUN apk add --no-cache openssl

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
