import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report
import joblib

# Load data
data = pd.read_csv("data/historical_weather.csv")

# Features (inputs) and target (output)
X = data[['rainfall_mm', 'humidity', 'pressure', 'temperature', 'wind_speed']]
y = data['landslide']

# Split into train/test
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# Train Random Forest (class_weight='balanced' handles rare landslide events)
model = RandomForestClassifier(n_estimators=100, random_state=42, class_weight='balanced')
model.fit(X_train, y_train)

# Evaluate
y_pred = model.predict(X_test)
print("✅ Model Accuracy:", accuracy_score(y_test, y_pred))
print(classification_report(y_test, y_pred))

# Save model
joblib.dump(model, 'landslide_model.pkl')
print("✅ Model saved as landslide_model.pkl")