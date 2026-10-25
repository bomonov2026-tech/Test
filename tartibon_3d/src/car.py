# TartibOn krossoveri: Blender (bpy) da protsedurali model.
# O'qlar: +X - oldinga, +Y - mashinaning chap tomoni, +Z - yuqoriga. Birlik: metr.
import math
import bmesh
import bpy
from mathutils import Vector

FONT = "/usr/share/fonts/opentype/inter/Inter-Bold.otf"
WHEEL_R = 0.355
AXLE_F, AXLE_R, TRACK = 1.32, -1.25, 0.80
XF, XR, ZB, ZT = 2.20, -2.20, 0.25, 1.15  # livery.py bilan bir xil


def material(name, color, rough=0.4, metal=0.0, emit=None, strength=0.0, coat=0.0):
    m = bpy.data.materials.new(name)
    m.use_nodes = True
    b = m.node_tree.nodes["Principled BSDF"]
    b.inputs["Base Color"].default_value = (*color, 1)
    b.inputs["Roughness"].default_value = rough
    b.inputs["Metallic"].default_value = metal
    if coat:
        b.inputs["Coat Weight"].default_value = coat
        b.inputs["Coat Roughness"].default_value = 0.03
    if emit:
        b.inputs["Emission Color"].default_value = (*emit, 1)
        b.inputs["Emission Strength"].default_value = strength
    return m


def paint_material(livery_path):
    m = material("Paint_livery", (0.85, 0.86, 0.88), rough=0.22, coat=1.0)
    nt = m.node_tree
    tex = nt.nodes.new("ShaderNodeTexImage")
    tex.image = bpy.data.images.load(livery_path)
    nt.links.new(tex.outputs["Color"], nt.nodes["Principled BSDF"].inputs["Base Color"])
    return m


class Mats:
    def __init__(self, livery_path):
        self.paint = paint_material(livery_path)
        self.white = material("Paint_white", (0.85, 0.86, 0.88), rough=0.22, coat=1.0)
        self.glass = material("Glass_tint", (0.008, 0.010, 0.013), rough=0.04, coat=1.0)
        self.plastic = material("Plastic_black", (0.02, 0.02, 0.022), rough=0.55)
        self.gloss = material("Gloss_black", (0.01, 0.01, 0.012), rough=0.12, coat=1.0)
        self.tire = material("Tire", (0.018, 0.018, 0.018), rough=0.85)
        self.rim = material("Rim_gunmetal", (0.16, 0.16, 0.17), rough=0.25, metal=0.9)
        self.red = material("Caliper_red", (0.55, 0.02, 0.02), rough=0.35, coat=0.5)
        self.chrome = material("Chrome", (0.9, 0.9, 0.9), rough=0.06, metal=1.0)
        self.gold = material("Bowtie_gold", (0.8, 0.58, 0.14), rough=0.25, metal=1.0)
        self.teal = material("Accent_teal", (0.03, 0.7, 0.6), rough=0.3, emit=(0.05, 0.85, 0.72), strength=3.0)
        self.green = material("Icon_green", (0.08, 0.7, 0.3), rough=0.3, emit=(0.1, 0.85, 0.35), strength=3.0)
        self.head = material("Headlight", (0.8, 0.9, 1.0), rough=0.1, emit=(0.75, 0.88, 1.0), strength=8.0)
        self.fog = material("Foglight_blue", (0.2, 0.5, 1.0), rough=0.1, emit=(0.25, 0.55, 1.0), strength=6.0)
        self.tail = material("Taillight", (0.5, 0.01, 0.01), rough=0.1, emit=(1.0, 0.03, 0.02), strength=2.0)
        self.amber = material("Indicator_amber", (0.6, 0.3, 0.02), rough=0.1, emit=(1.0, 0.45, 0.02), strength=0.0)
        self.plate_txt = material("Plate_white", (0.9, 0.9, 0.9), rough=0.4)


def link(obj, parent):
    bpy.context.scene.collection.objects.link(obj)
    if parent:
        obj.parent = parent
    return obj


def mesh_obj(name, bm, mat, parent, smooth=False):
    me = bpy.data.meshes.new(name)
    bm.to_mesh(me)
    bm.free()
    if smooth:
        me.shade_smooth()
        me.set_sharp_from_angle(angle=math.radians(35))
    o = bpy.data.objects.new(name, me)
    o.data.materials.append(mat)
    return link(o, parent)


def bake(o):
    """Modifikatorlarni qo'llab, oddiy mesh bilan almashtiradi."""
    dg = bpy.context.evaluated_depsgraph_get()
    me = bpy.data.meshes.new_from_object(o.evaluated_get(dg))
    o.modifiers.clear()
    o.data = me
    return o


def prism(name, profile, half_w, mat, parent, taper=None, bevel=0.0, segs=4):
    bm = bmesh.new()
    a = [bm.verts.new((x, half_w, z)) for x, z in profile]
    b = [bm.verts.new((x, -half_w, z)) for x, z in profile]
    bm.faces.new(a)
    bm.faces.new(list(reversed(b)))
    n = len(profile)
    for i in range(n):
        j = (i + 1) % n
        bm.faces.new((a[i], a[j], b[j], b[i]))
    bmesh.ops.recalc_face_normals(bm, faces=bm.faces)
    if taper:
        for v in bm.verts:
            v.co.y *= taper(v.co.x, v.co.z)
    o = mesh_obj(name, bm, mat, parent)
    if bevel:
        m = o.modifiers.new("bevel", "BEVEL")
        m.width, m.segments, m.limit_method = bevel, segs, "ANGLE"
        m.angle_limit = math.radians(25)
        m.harden_normals = False
    return o


def box(name, loc, size, mat, parent, rot=(0, 0, 0), bevel=0.0):
    bm = bmesh.new()
    bmesh.ops.create_cube(bm, size=1.0)
    for v in bm.verts:
        v.co = Vector((v.co.x * size[0], v.co.y * size[1], v.co.z * size[2]))
    o = mesh_obj(name, bm, mat, parent)
    o.location, o.rotation_euler = loc, rot
    if bevel:
        m = o.modifiers.new("bevel", "BEVEL")
        m.width, m.segments = bevel, 3
    return o


def cylinder(name, loc, r, depth, mat, parent, axis="Y", segs=48, bevel=0.0):
    bm = bmesh.new()
    bmesh.ops.create_cone(bm, cap_ends=True, segments=segs, radius1=r, radius2=r, depth=depth)
    o = mesh_obj(name, bm, mat, parent, smooth=True)
    o.location = loc
    o.rotation_euler = {"X": (0, math.pi / 2, 0), "Y": (math.pi / 2, 0, 0), "Z": (0, 0, 0)}[axis]
    if bevel:
        m = o.modifiers.new("bevel", "BEVEL")
        m.width, m.segments = bevel, 4
    return o


def ring(name, c, r_in, r_out, a0, a1, y0, y1, mat, parent, segs=40):
    """XZ tekisligidagi halqa bo'lagi (a0..a1 gradus), Y bo'yicha qalinlikda."""
    bm = bmesh.new()
    rows = []
    for i in range(segs + 1):
        a = math.radians(a0 + (a1 - a0) * i / segs)
        ca, sa = math.cos(a), math.sin(a)
        rows.append([bm.verts.new((c[0] + r * ca, c[1] + y, c[2] + r * sa))
                     for r, y in ((r_in, y0), (r_out, y0), (r_out, y1), (r_in, y1))])
    for i in range(segs):
        for k in range(4):
            bm.faces.new((rows[i][k], rows[i][(k + 1) % 4], rows[i + 1][(k + 1) % 4], rows[i + 1][k]))
    if (a1 - a0) % 360:
        bm.faces.new(rows[0])
        bm.faces.new(list(reversed(rows[-1])))
    bmesh.ops.recalc_face_normals(bm, faces=bm.faces)
    return mesh_obj(name, bm, mat, parent, smooth=True)


def text(name, body, loc, rot, size, mats, parent, green_from=None):
    cu = bpy.data.curves.new(name, "FONT")
    cu.body, cu.size, cu.extrude = body, size, 0.002
    cu.font = bpy.data.fonts.load(FONT, check_existing=True)
    cu.align_x, cu.align_y = "CENTER", "CENTER"
    for m in mats:
        cu.materials.append(m)
    if green_from is not None:
        for i in range(green_from, len(body)):
            cu.body_format[i].material_index = 1
    o = bpy.data.objects.new(name, cu)
    o.location, o.rotation_euler = loc, rot
    return link(o, parent)


def power_icon(name, c, r, mat, parent, y_th):
    ring(name + "_arc", c, r * 0.72, r, 120, 420, -y_th, y_th, mat, parent, segs=24)
    box(name + "_bar", (c[0], c[1], c[2] + r * 0.55), (r * 0.26, 2 * y_th, r * 0.95), mat, parent)


def body_uvs(o, m):
    """Yon yuzalarga livery atlasini proyeksiyalaydi, qolganiga oq piksel beradi."""
    me = o.data
    me.materials.clear()
    me.materials.append(m.paint)
    me.materials.append(m.plastic)
    uv = me.uv_layers.new(name="UVMap")
    for f in me.polygons:
        n = f.normal
        c = f.center
        in_well = any(abs(c.x - ax) < 0.55 for ax in (AXLE_F, AXLE_R)) and c.z < 0.82 and abs(n.y) < 0.35
        if n.z < -0.5 or in_well:
            f.material_index = 1
        for li in f.loop_indices:
            p = me.vertices[me.loops[li].vertex_index].co
            v = min(max((p.z - ZB) / (ZT - ZB), 0.0), 1.0)
            if n.y > 0.35:
                uv.data[li].uv = ((XF - p.x) / (XF - XR), 0.5 + 0.5 * v)
            elif n.y < -0.35:
                uv.data[li].uv = ((p.x - XR) / (XF - XR), 0.5 * v)
            else:
                uv.data[li].uv = (0.001, 0.999)
    me.shade_smooth()
    me.set_sharp_from_angle(angle=math.radians(35))


def wheel(name, x, side, m, root):
    steer = bpy.data.objects.new(name + "_steer", None)
    steer.location = (x, side * TRACK, WHEEL_R)
    link(steer, root)
    spin = bpy.data.objects.new(name + "_spin", None)
    link(spin, steer)
    cylinder(name + "_tire", (0, 0, 0), WHEEL_R, 0.235, m.tire, spin, segs=64, bevel=0.045)
    o = side * 0.122
    cylinder(name + "_rim", (0, o * 0.9, 0), 0.245, 0.02, m.gloss, spin, segs=64)
    ring(name + "_lip", (0, 0, 0), 0.238, 0.252, 0, 360, o, o + side * 0.012, m.red, spin, segs=64)
    for i in range(10):
        a = 2 * math.pi * i / 10
        box(f"{name}_spoke{i}", (0.135 * math.cos(a), o + side * 0.006, 0.135 * math.sin(a)),
            (0.20, 0.018, 0.028), m.rim, spin, rot=(0, -a, 0))
    cylinder(name + "_hub", (0, o + side * 0.01, 0), 0.055, 0.02, m.rim, spin, segs=32)
    # Kaliper aylanmaydi: steer'ga biriktiriladi
    box(name + "_caliper", (-0.14 if x > 0 else 0.14, side * 0.07, 0.10), (0.10, 0.05, 0.12), m.red, steer, bevel=0.01)
    return steer, spin


def build_car(livery_path, name="TartibOn"):
    m = Mats(livery_path)
    root = bpy.data.objects.new(name, None)
    link(root, None)

    # --- Pastki kuzov (bamperdan belbog' chizig'igacha) ---
    lower = [(2.08, 0.28), (2.17, 0.42), (2.19, 0.64), (2.15, 0.84), (2.02, 0.92), (1.40, 1.00),
             (0.92, 1.05), (-0.40, 1.06), (-1.70, 1.10), (-2.02, 1.08), (-2.12, 0.96), (-2.16, 0.60),
             (-2.10, 0.28)]
    taper = lambda x, z: (0.95 if x > 2.05 else 0.96 if x < -2.0 else 1.0) * (0.97 if z > 1.0 else 1.0)
    body = prism("Body", lower, 0.90, m.paint, root, taper, bevel=0.10, segs=5)
    for ax in (AXLE_F, AXLE_R):
        for s in (1, -1):
            cut = cylinder(f"cut_{ax}_{s}", (ax, s * 0.85, WHEEL_R), 0.43, 0.6, m.plastic, None, segs=64)
            bm_ = body.modifiers.new(f"well_{ax}_{s}", "BOOLEAN")
            bm_.object, bm_.operation = cut, "DIFFERENCE"
            cut.hide_render = cut.hide_viewport = True
    bake(body)
    for o in [o for o in bpy.data.objects if o.name.startswith("cut_")]:
        bpy.data.objects.remove(o)
    body_uvs(body, m)

    # --- Kabina (oynalar), tom va panorama lyuk ---
    green = [(0.98, 1.00), (0.05, 1.52), (-0.20, 1.56), (-1.40, 1.57), (-1.82, 1.48), (-1.98, 1.12), (-1.95, 1.00)]
    bake(prism("Greenhouse", green, 0.80, m.glass, root, lambda x, z: 0.86 if z > 1.4 else 1.0, bevel=0.06))
    roof = [(0.10, 1.50), (-0.20, 1.578), (-1.40, 1.588), (-1.80, 1.50), (-1.70, 1.46), (0.0, 1.46)]
    bake(prism("Roof", roof, 0.71, m.white, root, bevel=0.03))
    box("Sunroof", (-0.62, 0, 1.592), (1.0, 1.0, 0.012), m.gloss, root, bevel=0.004)
    for s in (1, -1):
        box(f"Roof_rail_{s}", (-0.70, s * 0.60, 1.615), (1.45, 0.04, 0.035), m.plastic, root, bevel=0.01)
        box(f"B_pillar_{s}", (-0.22, s * 0.752, 1.27), (0.09, 0.03, 0.54), m.gloss, root, rot=(s * math.radians(11.3), 0, 0))
        box(f"Mirror_{s}", (0.78, s * 0.95, 1.10), (0.20, 0.16, 0.12), m.gloss, root, bevel=0.03)
        box(f"Mirror_ind_{s}", (0.80, s * 1.028, 1.10), (0.12, 0.01, 0.025), m.amber, root)

    # --- G'ildirak arkalari va ostonalar (qora plastik) ---
    for ax in (AXLE_F, AXLE_R):
        for s in (1, -1):
            y0, y1 = (0.84, 0.93) if s > 0 else (-0.93, -0.84)
            ring(f"Arch_{ax}_{s}", (ax, 0, WHEEL_R), 0.43, 0.51, -12, 192, y0, y1, m.plastic, root)
    for s in (1, -1):
        box(f"Sill_{s}", (0.03, s * 0.885, 0.34), (1.55, 0.07, 0.13), m.plastic, root, bevel=0.02)
        box(f"Sill_teal_{s}", (0.03, s * 0.922, 0.40), (1.45, 0.008, 0.015), m.teal, root)

    # --- Old qism ---
    box("Grille", (2.175, 0, 0.58), (0.06, 1.10, 0.26), m.gloss, root, bevel=0.02)
    box("Grille_chrome", (2.21, 0, 0.66), (0.012, 1.0, 0.012), m.chrome, root)
    box("Bowtie", (2.205, 0, 0.62), (0.02, 0.17, 0.05), m.gold, root, bevel=0.005)
    box("Splitter", (2.10, 0, 0.30), (0.22, 1.72, 0.05), m.plastic, root, bevel=0.01)
    box("Splitter_teal", (2.205, 0, 0.335), (0.015, 1.50, 0.015), m.teal, root)
    box("Plate_front", (2.205, 0, 0.44), (0.02, 0.52, 0.12), m.gloss, root, bevel=0.01)
    text("Plate_front_txt", "TartibOn", (2.218, 0, 0.44), (math.pi / 2, 0, math.pi / 2), 0.075,
         [m.plate_txt, m.green], root, green_from=6)
    for s in (1, -1):
        box(f"Headlight_{s}", (2.125, s * 0.58, 0.83), (0.10, 0.46, 0.055), m.head, root,
            rot=(0, math.radians(-12), s * math.radians(-10)), bevel=0.01)
        box(f"Indicator_F_{s}", (2.15, s * 0.74, 0.76), (0.08, 0.14, 0.025), m.amber, root)
        box(f"DRL_{s}", (2.15, s * 0.70, 0.70), (0.04, 0.24, 0.018), m.fog, root, rot=(s * math.radians(10), 0, 0))
        cylinder(f"Fog_{s}", (2.165, s * 0.70, 0.45), 0.05, 0.04, m.fog, root, axis="X", segs=32)

    # --- Orqa qism ---
    box("Diffuser", (-2.10, 0, 0.37), (0.16, 1.72, 0.20), m.plastic, root, bevel=0.02)
    box("Plate_rear", (-2.19, 0, 0.62), (0.02, 0.52, 0.12), m.gloss, root, bevel=0.01)
    text("Plate_rear_txt", "TartibOn", (-2.203, 0, 0.62), (math.pi / 2, 0, -math.pi / 2), 0.075,
         [m.plate_txt, m.green], root, green_from=6)
    text("Rear_logo", "TartibOn", (-2.155, 0, 0.84), (math.pi / 2, 0, -math.pi / 2), 0.11,
         [material("Logo_navy", (0.03, 0.08, 0.3), rough=0.3), m.green], root, green_from=6)
    for s in (1, -1):
        box(f"Taillight_{s}", (-2.09, s * 0.62, 0.99), (0.10, 0.42, 0.09), m.tail, root, bevel=0.01)
        box(f"Indicator_R_{s}", (-2.10, s * 0.76, 0.93), (0.08, 0.14, 0.025), m.amber, root)
        cylinder(f"Exhaust_{s}", (-2.19, s * 0.42, 0.36), 0.045, 0.10, m.chrome, root, axis="X", segs=32)
    box("Brake_light_top", (-1.86, 0, 1.53), (0.03, 0.40, 0.02), m.tail, root)

    # --- Spoyler ---
    box("Wing", (-1.98, 0, 1.60), (0.36, 1.50, 0.035), m.gloss, root, rot=(0, math.radians(-6), 0), bevel=0.01)
    for s in (1, -1):
        box(f"Wing_strut_{s}", (-1.90, s * 0.40, 1.53), (0.14, 0.035, 0.14), m.gloss, root)
        box(f"Wing_end_{s}", (-1.98, s * 0.755, 1.60), (0.38, 0.02, 0.12), m.gloss, root, bevel=0.005)
        power_icon(f"Wing_icon_{s}", (-1.98, s * 0.767, 1.60), 0.04, m.green, root, 0.004)
    cylinder("Antenna", (-1.55, 0, 1.68), 0.006, 0.22, m.plastic, root, axis="Z", segs=12)

    wheels = {}
    for nm, x in (("FL", AXLE_F), ("RL", AXLE_R)):
        wheels[nm] = wheel(nm, x, 1, m, root)
    for nm, x in (("FR", AXLE_F), ("RR", AXLE_R)):
        wheels[nm] = wheel(nm, x, -1, m, root)

    for o in list(bpy.data.objects):
        if o.type == "MESH" and o.modifiers:
            bake(o)
    return root, wheels, m
