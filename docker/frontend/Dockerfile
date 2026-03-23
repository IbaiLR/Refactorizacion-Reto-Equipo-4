# Build de múltiples etapas para Vue.js

# Etapa 1: Compilar Vue
FROM node:20-alpine AS build

WORKDIR /app

# Copiar package.json del frontend
COPY package*.json ./

# Instalar dependencias
RUN npm ci

# Copiar código fuente
COPY . ./

# Variable de entorno para la api
ENV VITE_API_BASE_URL=""

# Build para producción
RUN npm run build-only

# Etapa 2: Servidor Nginx
FROM nginx:alpine

# Copiar archivos compilados
COPY --from=build /app/dist /usr/share/nginx/html

# Config nginx para SPA routing (Vue Router con HTML5 history)
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]