# Step 1: Build the SvelteKit project
FROM node:18 AS build

# Set the working directory
WORKDIR /app

# Copy package.json and package-lock.json to the working directory
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the application code to the working directory
COPY . .

# Build the SvelteKit project
RUN npm run build

# Step 2: Run the SvelteKit application
FROM node:18

# Set the working directory
WORKDIR /app

# Copy the built files from the previous stage to the working directory
COPY --from=build /app .

# Install production dependencies
RUN npm ci --production

# Expose port 3000 to the outside world
EXPOSE 3000

# Start the SvelteKit server
CMD ["node", "build"]
