import json
import sys
from pathlib import Path

import joblib

model_path = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(__file__).with_name('landslide_model.pkl')
model = joblib.load(model_path)
input_data = sys.stdin.read()
data = json.loads(input_data)

features = [[
    data['rainfall_mm'],
    data['humidity'],
    data['pressure'],
    data['temperature'],
    data['wind_speed']
]]

prediction = model.predict(features)[0]
print(json.dumps({'prediction': int(prediction)}))