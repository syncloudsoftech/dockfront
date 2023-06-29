# dockfront

Simple (emulated) [CDN](https://en.wikipedia.org/wiki/Content_delivery_network) server (similar to [CloudFront](https://aws.amazon.com/cloudfront/)) for [Docker](https://www.docker.com) with support for [S3](https://aws.amazon.com/s3/)-like and web origins.

## Usage

To launch a container from this image, you must have [Docker](https://www.docker.com) installed.
If already, run the below command:

```shell
# proxy a web origin
$ docker run -d --name dockfront -p 8080:80 -e WEB_URL=https://example.com syncloudsoftech/dockfront

# proxy an S3 origin
$ docker run -d --name dockfront -p 8080:80 \
  -e ORIGIN_TYPE=s3 \
  -e S3_ACCESS_KEY_ID=your_access_key_id \
  -e S3_BUCKET=your_bucket_name \
  -e S3_ENDPOINT=https://sgp1.digitaloceanspaces.com \
  -e S3_REGION=sgp1 \
  -e S3_SECRET_ACCESS_KEY=your_secret_access_key
```

To start/stop the (named) container at a later point in time, use below commnads:

```
# start "dockfront" named container
$ docker start dockfront

# stop "dockfront" named container
$ docker stop dockfront
```

Once running, you can access the files using [http://127.0.0.1:8080/](http://127.0.0.1:8080/) as base URL.

### docker-compose.yml

To include this container as a service in your existing `docker-compose.yml` setup, use below definition:

```yml
version: '3'

services:
  dockfront:
    image: syncloudsoftech/dockfront
    environment:
      WEB_URL: https://example.com
      WEB_USER_AGENT: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36'
    ports:
      - '8080:80'
```

Or if you are using it with [MinIO](https://min.io/):

```yml
version: '3'

services:
  dockfront:
    image: syncloudsoftech/dockfront
    environment:
      ORIGIN_TYPE: s3
      S3_ACCESS_KEY_ID: dockfront
      S3_BUCKET: dockfront
      S3_ENDPOINT: http://minio:9091
      S3_PATH_STYLE_ENDPOINT: 'true'
      S3_REGION: us-east-1
      S3_SECRET_ACCESS_KEY: dockfront
    ports:
      - '8080:80'

  minio:
    image: minio/minio
    command: minio server /data/minio --address ":9091" --console-address ":9092"
    environment:
      MINIO_ROOT_USER: dockfront
      MINIO_ROOT_PASSWORD: dockfront
    ports:
      - '9091:9091'
      - '9092:9092'
    volumes:
      - minio-data:/data/minio

volumes:
  minio-data:
```

## Development

Building or modifying the container yourself from source is also quite easy.
Just clone the repository and run below command:

```shell
$ docker build -t dockfront .
```

Run the locally built container as follows:

```shell
$ docker run -it -p 8080:80 -e WEB_URL=https://example.com dockfront
```

## License

See the [LICENSE](LICENSE) file.
