FROM golang:1.22-alpine AS build
WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -o /recipe-import ./cmd/server

FROM alpine:3.20
RUN apk add --no-cache ca-certificates
COPY --from=build /recipe-import /usr/local/bin/recipe-import
ENV LISTEN_ADDR=:8083
EXPOSE 8083
ENTRYPOINT ["/usr/local/bin/recipe-import"]
