FROM alpine:3.18

# Install dependencies
RUN apk add --no-cache \
    bash \
    mysql-client \
    redis \
    aws-cli \
    curl \
    gzip \
    tar

# Create backup user
RUN addgroup -g 1000 backup && \
    adduser -D -u 1000 -G backup backup

# Copy backup scripts
COPY scripts/ /scripts/
RUN chmod +x /scripts/*.sh

# Create backup directory
RUN mkdir -p /backups && chown -R backup:backup /backups

USER backup

# Run cron
CMD ["/scripts/cron.sh"]
