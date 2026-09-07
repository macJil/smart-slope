import pandas as pd
import numpy as np
import os

np.random.seed(42)

# Generate 1000 days of synthetic weather data
n = 1000
dates = pd.date_range(start="2020-01-01", periods=n, freq="D")

rainfall = np.random.exponential(scale=50, size=n)
humidity = np.random.uniform(60, 95, size=n)
pressure = np.random.uniform(1000, 1020, size=n)
temperature = np.random.uniform(15, 30, size=n)
wind_speed = np.random.uniform(0, 10, size=n)

# Label as landslide (1) if rainfall > 100mm (Baguio's critical threshold)
landslide = (rainfall > 100).astype(int)

data = pd.DataFrame({
    "date": dates,
    "rainfall_mm": rainfall,
    "humidity": humidity,
    "pressure": pressure,
    "temperature": temperature,
    "wind_speed": wind_speed,
    "landslide": landslide
})

os.makedirs("data", exist_ok=True)
data.to_csv("data/historical_weather.csv", index=False)
print("✅ Generated data/historical_weather.csv with", len(data), "rows")