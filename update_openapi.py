import os

file_path = "docs/pos/openapi-pos.yaml"

with open(file_path, "r") as f:
    content = f.read()

addition = """
  /api/pos/auth/operator/login:
    post:
      summary: Operator login (Sanctum token)
      tags: [Auth]
      security: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [email, password]
              properties:
                email: { type: string, format: email }
                password: { type: string }
      responses:
        '200':
          description: Login successful
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ApiResponse'
        '401':
          description: Invalid credentials
        '403':
          description: Role not allowed for POS

  /api/pos/auth/operator/logout:
    post:
      summary: Operator logout
      tags: [Auth]
      responses:
        '200':
          description: Logged out

  /api/pos/auth/operator/me:
    get:
      summary: Get current operator info
      tags: [Auth]
      responses:
        '200':
          description: Operator profile
"""

if "/api/pos/auth/operator/login" not in content:
    content = content.rstrip() + "\n" + addition
    with open(file_path, "w") as f:
        f.write(content)
    print("OpenAPI updated: +3 auth endpoints")
else:
    print("Already present")
