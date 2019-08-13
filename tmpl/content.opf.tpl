<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="BookId" version="2.0">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
    <dc:identifier id="BookId">%bookID%</dc:identifier>
    <dc:title>%title%</dc:title>
    <dc:creator>%creator%</dc:creator>
    <dc:language>%language%</dc:language>
    <dc:date>%date%</dc:date>
    <meta name="cover" content="coverimg"/>
  </metadata>
  <manifest>
    <item href="toc.ncx" id="ncx" media-type="application/x-dtbncx+xml" />
    <item href="Styles/style.css" id="style.css" media-type="text/css" />
    %item%
    
  </manifest>
  <spine toc="ncx">
    %itemref%
  </spine>
  <guide />
</package>
