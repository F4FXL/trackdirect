FROM ubuntu:24.04

RUN apt-get update && apt-get install -y \
  gnupg \
  && rm -rf /var/lib/apt/lists/*

RUN gpg --keyserver keyserver.ubuntu.com --recv D43AD4708A2DA1139F250B3294E40E5320D8AE3C 
RUN gpg --export D43AD4708A2DA1139F250B3294E40E5320D8AE3C > /usr/share/keyrings/aprsc-archive-keyring.gpg
RUN printf "deb [arch=amd64 signed-by=/usr/share/keyrings/aprsc-archive-keyring.gpg] http://aprsc-dist.he.fi/aprsc/apt noble main" >> /etc/apt/sources.list

RUN apt-get update && apt-get install -y \
  aprsc \
  && rm -rf /var/lib/apt/lists/*

EXPOSE 10152
EXPOSE 14580
EXPOSE 10155
EXPOSE 14501

WORKDIR /opt/aprsc
USER aprsc

COPY config/aprsc.conf /opt/aprsc/etc/aprsc.conf
CMD /opt/aprsc/sbin/aprsc -c /opt/aprsc/etc/aprsc.conf
