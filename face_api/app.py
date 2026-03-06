from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import base64
import os
from PIL import Image
import io
import traceback

app = Flask(__name__)
CORS(app)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok'})

@app.route('/compare', methods=['POST'])
def compare_faces():
    try:
        from deepface import DeepFace

        data = request.get_json()

        print(f"=== INCOMING REQUEST ===")
        print(f"Keys received: {list(data.keys()) if data else 'NO DATA'}")

        captured_b64 = data.get('captured_image')
        reference_path = data.get('reference_path')

        print(f"=== COMPARE ===")
        print(f"Path reçu: '{reference_path}'")
        print(f"Existe: {os.path.exists(reference_path) if reference_path else False}")
        print(f"Image size: {len(captured_b64) if captured_b64 else 0}")

        if not captured_b64 or not reference_path:
            return jsonify({'success': False, 'message': 'Missing data'}), 400

        # ===== CHARGE L'IMAGE CAPTURÉE =====
        if ',' in captured_b64:
            captured_b64 = captured_b64.split(',')[1]

        captured_bytes = base64.b64decode(captured_b64)
        captured_pil = Image.open(io.BytesIO(captured_bytes)).convert('RGB')

        # Sauvegarde temporaire pour DeepFace
        temp_path = 'temp_captured.jpg'
        captured_pil.save(temp_path, 'JPEG')

        # ===== CHARGE LA PHOTO DE RÉFÉRENCE =====
        if not os.path.exists(reference_path):
            return jsonify({'success': False, 'message': 'Reference photo not found'}), 404

        # ===== COMPARE LES VISAGES avec DeepFace =====
        print("Comparing faces with DeepFace...")
        result = DeepFace.verify(
            img1_path=temp_path,
            img2_path=reference_path,
            model_name='VGG-Face',
            enforce_detection=False
        )

        # Nettoyage fichier temp
        if os.path.exists(temp_path):
            os.remove(temp_path)

        distance = result['distance']
        is_match = result['verified']
        confidence = round((1 - float(distance)) * 100, 2)

        print(f"Distance: {distance}, Match: {is_match}, Confidence: {confidence}")

        return jsonify({
            'success': True,
            'match': is_match,
            'distance': float(distance),
            'confidence': confidence,
            'message': 'Face matched!' if is_match else 'Face not recognized'
        })

    except Exception as e:
        print(f"=== ERROR IN /compare ===")
        traceback.print_exc()
        if os.path.exists('temp_captured.jpg'):
            os.remove('temp_captured.jpg')
        return jsonify({'success': False, 'message': str(e)}), 500


@app.route('/encode', methods=['POST'])
def encode_face():
    try:
        from deepface import DeepFace

        data = request.get_json()
        image_path = data.get('image_path')

        if not image_path or not os.path.exists(image_path):
            return jsonify({'success': False, 'message': 'Image not found'}), 404

        # Vérifie qu'un visage est détectable
        result = DeepFace.extract_faces(img_path=image_path, enforce_detection=True)

        if not result:
            return jsonify({'success': False, 'message': 'No face detected in photo'}), 400

        return jsonify({'success': True, 'has_face': True, 'message': 'Face encoded successfully'})

    except Exception as e:
        traceback.print_exc()
        return jsonify({'success': False, 'message': str(e)}), 500


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=False)