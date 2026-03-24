FROM golang:1.22-alpine AS build
WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -o /meal-planning ./cmd/server

FROM alpine:3.20
RUN apk add --no-cache ca-certificates
COPY --from=build /meal-planning /usr/local/bin/meal-planning
ENV LISTEN_ADDR=:8084
EXPOSE 8084
ENTRYPOINT ["/usr/local/bin/meal-planning"]
