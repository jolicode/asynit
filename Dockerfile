FROM golang:1.9-alpine3.7

RUN apk --update add git
RUN go get github.com/ahmetb/go-httpbin/cmd/httpbin

CMD ["bin/httpbin","-host",":80"]
