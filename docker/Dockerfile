FROM jrottenberg/ffmpeg:7-alpine AS FFmpeg
FROM php:fpm-alpine3.20

# ручной вариант
#RUN apk update && apk --no-cache add -f \
#    unzip \
#    ffmpeg

COPY --from=FFmpeg /usr/bin/ffmpeg /usr/bin/ffmpeg
COPY --from=FFmpeg /usr/bin/ffprobe /usr/bin/ffprobe

WORKDIR /var/www

CMD ["php-fpm"]
