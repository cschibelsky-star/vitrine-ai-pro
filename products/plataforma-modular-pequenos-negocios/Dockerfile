FROM node:22-alpine
WORKDIR /app
COPY package.json server.js ./
RUN mkdir -p /app/data
EXPOSE 8788
CMD ["node","server.js"]
