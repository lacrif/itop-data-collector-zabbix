FROM lacrif/itop-data-collector-base:1.4.1

RUN mkdir -p /opt/itop-data-collector-base/collectors
COPY ./ /opt/itop-data-collector-base/collectors
