"use client"

import { useState } from "react"
import { SidebarTrigger } from "@/components/ui/sidebar"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Checkbox } from "@/components/ui/checkbox"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Input } from "@/components/ui/input"
import { Search, Trash2, Save } from "lucide-react"

interface Course {
  id: string
  kode: string
  nama: string
  sks: number
  semester: number
  dosen: string
  jadwal: string
  ruang: string
  kuota: number
  terisi: number
  prasyarat?: string[]
}

const availableCourses: Course[] = [
  {
    id: "1",
    kode: "CS101",
    nama: "Algoritma dan Pemrograman",
    sks: 3,
    semester: 1,
    dosen: "Dr. Ahmad Wijaya",
    jadwal: "Senin 08:00-10:30",
    ruang: "Lab Komputer 1",
    kuota: 40,
    terisi: 35,
  },
  {
    id: "2",
    kode: "CS201",
    nama: "Struktur Data",
    sks: 3,
    semester: 3,
    dosen: "Prof. Siti Nurhaliza",
    jadwal: "Rabu 10:30-13:00",
    ruang: "Lab Komputer 2",
    kuota: 35,
    terisi: 30,
    prasyarat: ["CS101"],
  },
  {
    id: "3",
    kode: "MTK101",
    nama: "Kalkulus I",
    sks: 4,
    semester: 1,
    dosen: "Dr. Budi Santoso",
    jadwal: "Selasa 08:00-11:30",
    ruang: "Ruang 201",
    kuota: 50,
    terisi: 45,
  },
  {
    id: "4",
    kode: "CS301",
    nama: "Basis Data",
    sks: 3,
    semester: 5,
    dosen: "Dr. Lisa Permata",
    jadwal: "Kamis 13:00-15:30",
    ruang: "Lab Database",
    kuota: 30,
    terisi: 25,
    prasyarat: ["CS201"],
  },
  {
    id: "5",
    kode: "CS302",
    nama: "Rekayasa Perangkat Lunak",
    sks: 3,
    semester: 5,
    dosen: "Prof. Andi Kurniawan",
    jadwal: "Jumat 08:00-10:30",
    ruang: "Ruang 301",
    kuota: 35,
    terisi: 28,
  },
]

export default function KRSPage() {
  const [selectedCourses, setSelectedCourses] = useState<Course[]>([])
  const [searchTerm, setSearchTerm] = useState("")

  const filteredCourses = availableCourses.filter(
    (course) =>
      course.nama.toLowerCase().includes(searchTerm.toLowerCase()) ||
      course.kode.toLowerCase().includes(searchTerm.toLowerCase()) ||
      course.dosen.toLowerCase().includes(searchTerm.toLowerCase()),
  )

  const totalSKS = selectedCourses.reduce((sum, course) => sum + course.sks, 0)

  const handleCourseSelect = (course: Course, checked: boolean) => {
    if (checked) {
      setSelectedCourses([...selectedCourses, course])
    } else {
      setSelectedCourses(selectedCourses.filter((c) => c.id !== course.id))
    }
  }

  const removeCourse = (courseId: string) => {
    setSelectedCourses(selectedCourses.filter((c) => c.id !== courseId))
  }

  const isSelected = (courseId: string) => {
    return selectedCourses.some((c) => c.id === courseId)
  }

  const canSelectCourse = (course: Course) => {
    if (course.terisi >= course.kuota) return false
    if (totalSKS + course.sks > 24) return false
    return true
  }

  return (
    <div className="flex flex-col">
      {/* Header */}
      <header className="flex h-16 shrink-0 items-center gap-2 border-b bg-white px-4">
        <SidebarTrigger className="-ml-1" />
        <div className="flex-1">
          <h1 className="text-xl font-semibold">Pengisian KRS</h1>
          <p className="text-sm text-gray-500">Semester Genap 2023/2024</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="outline">Total SKS: {totalSKS}/24</Badge>
          <Button disabled={selectedCourses.length === 0}>
            <Save className="h-4 w-4 mr-2" />
            Simpan KRS
          </Button>
        </div>
      </header>

      <div className="flex-1 p-6 space-y-6">
        {/* Selected Courses */}
        {selectedCourses.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Mata Kuliah Terpilih</CardTitle>
              <CardDescription>Daftar mata kuliah yang akan diambil semester ini</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {selectedCourses.map((course) => (
                  <div key={course.id} className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex-1">
                      <div className="flex items-center gap-3">
                        <Badge variant="secondary">{course.kode}</Badge>
                        <span className="font-medium">{course.nama}</span>
                        <Badge>{course.sks} SKS</Badge>
                      </div>
                      <p className="text-sm text-gray-500 mt-1">
                        {course.dosen} • {course.jadwal} • {course.ruang}
                      </p>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => removeCourse(course.id)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Available Courses */}
        <Card>
          <CardHeader>
            <CardTitle>Mata Kuliah Tersedia</CardTitle>
            <CardDescription>Pilih mata kuliah yang ingin diambil</CardDescription>
            <div className="flex items-center gap-2">
              <div className="relative flex-1 max-w-sm">
                <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-500" />
                <Input
                  placeholder="Cari mata kuliah..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-8"
                />
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12"></TableHead>
                  <TableHead>Kode</TableHead>
                  <TableHead>Mata Kuliah</TableHead>
                  <TableHead>SKS</TableHead>
                  <TableHead>Dosen</TableHead>
                  <TableHead>Jadwal</TableHead>
                  <TableHead>Ruang</TableHead>
                  <TableHead>Kuota</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredCourses.map((course) => {
                  const selected = isSelected(course.id)
                  const canSelect = canSelectCourse(course)
                  const isFull = course.terisi >= course.kuota

                  return (
                    <TableRow key={course.id} className={selected ? "bg-blue-50" : ""}>
                      <TableCell>
                        <Checkbox
                          checked={selected}
                          disabled={!canSelect && !selected}
                          onCheckedChange={(checked) => handleCourseSelect(course, checked as boolean)}
                        />
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{course.kode}</Badge>
                      </TableCell>
                      <TableCell className="font-medium">
                        {course.nama}
                        {course.prasyarat && (
                          <div className="text-xs text-gray-500 mt-1">Prasyarat: {course.prasyarat.join(", ")}</div>
                        )}
                      </TableCell>
                      <TableCell>{course.sks}</TableCell>
                      <TableCell>{course.dosen}</TableCell>
                      <TableCell>{course.jadwal}</TableCell>
                      <TableCell>{course.ruang}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <span className={isFull ? "text-red-600" : "text-green-600"}>
                            {course.terisi}/{course.kuota}
                          </span>
                          {isFull && (
                            <Badge variant="destructive" className="text-xs">
                              Penuh
                            </Badge>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
