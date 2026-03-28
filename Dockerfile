# Default Dockerfile
#
# @link     https://www.hyperf.io
# @document https://hyperf.wiki
# @contact  group@hyperf.io
# @license  https://github.com/hyperf/hyperf/blob/master/LICENSE

FROM hyperf/hyperf:8.2-alpine-v3.20-swoole
LABEL maintainer="Hyperf Developers <group@hyperf.io>" version="1.0" license="MIT" app.name="Hyperf"

RUN composer config -g repos.packahist composer https://mirrors.aliyun.com/composer

WORKDIR /app

COPY . .

RUN composer dump-autoload --no-dev

EXPOSE 8765

CMD ["php", "bin/hyperf.php"]
