FROM golang:1.22-bookworm AS build
WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -o /recipe-import ./cmd/server

FROM scratch
COPY --from=build /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=build /recipe-import /usr/local/bin/recipe-import
ENV LISTEN_ADDR=:8083
EXPOSE 8083
ENTRYPOINT ["/usr/local/bin/recipe-import"]
