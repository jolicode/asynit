FROM golang:1.22-alpine

RUN go install github.com/ahmetb/go-httpbin/cmd/httpbin@latest

CMD ["bin/httpbin","-host",":80"]
